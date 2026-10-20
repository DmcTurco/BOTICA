<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DocumentSeries;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Datos de un cliente que migra desde otro sistema (productos con su stock).
 *
 * Cada "pack" es una carpeta en database/data/client_packs/<nombre>/ con un productos.csv.
 * Al crear la compañía se elige el pack y esta clase lo carga en ella y en su primera sede:
 * productos (con código de barras, laboratorio, categoría y precios), presentaciones (unidad,
 * blíster, caja), stock de la sede, kardex ("Inventario inicial") y lotes con vencimiento.
 *
 * Requiere el catálogo base de la compañía (CompanyStarterCatalog): usa sus unidades y reutiliza
 * sus laboratorios y categorías; crea solo los que falten. Debe llamarse dentro de una transacción.
 *
 * Columnas de productos.csv:
 *   barcode, name, category, laboratory, active_ingredient, stock, purchase_price, sale_price,
 *   stock_min, stock_max, expiration (Y-m-d), requires_recipe (0/1),
 *   pack_qty, pack_price (caja), blister_qty, blister_price
 * El stock está en la unidad base (la más pequeña que se vende).
 */
class CompanyClientDataPack
{
    public const DIRECTORY = 'database/data/client_packs';

    public const COLUMNS = [
        'barcode', 'name', 'category', 'laboratory', 'active_ingredient', 'stock', 'purchase_price', 'sale_price',
        'stock_min', 'stock_max', 'expiration', 'requires_recipe', 'pack_qty', 'pack_price', 'blister_qty', 'blister_price',
    ];

    /** Nota que queda en el kardex de cada producto cargado */
    private const NOTE = 'Inventario inicial (migración de datos del cliente)';

    /** Packs disponibles: nombres de las carpetas que tienen productos.csv */
    public static function available(): array
    {
        $names = [];
        foreach (glob(base_path(self::DIRECTORY) . '/*/productos.csv') ?: [] as $file) {
            $names[] = basename(dirname($file));
        }
        sort($names);

        return $names;
    }

    /** Cantidad de productos de un pack (para mostrarlo en el formulario) */
    public static function size(string $pack): int
    {
        $path = self::path($pack);

        return is_file($path) ? max(0, count(file($path, FILE_SKIP_EMPTY_LINES)) - 1) : 0;
    }

    /** Nombre legible del pack: "botica_17102026" → "Botica 17102026" */
    public static function label(string $pack): string
    {
        return Str::of($pack)->replace(['_', '-'], ' ')->squish()->ucfirst()->toString();
    }

    /**
     * Carga el pack en la compañía y su sede. Devuelve ['products' => n, 'with_stock' => n, 'units' => n].
     */
    public static function seed(Company $company, Branch $branch, string $pack): array
    {
        return self::seedRows($company, $branch, self::rows($pack));
    }

    /**
     * Carga filas ya leídas (con las claves de COLUMNS) en la compañía y su sede. Lo usan el pack
     * del proyecto y la importación subida desde el panel (ClientProductImport). Dentro de una transacción.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function seedRows(Company $company, Branch $branch, array $items): array
    {
        if (!$items) {
            return ['products' => 0, 'with_stock' => 0, 'units' => 0];
        }

        // Unidades que usan los productos: si la compañía borró alguna del catálogo base, se vuelve a crear
        $units = [];
        foreach ([['Unidad', 'UND', 'NIU'], ['Caja', 'CJA', 'BX'], ['Blíster', 'BLI', 'NIU']] as [$name, $abbreviation, $sunatCode]) {
            $units[$name] = $company->units()->firstOrCreate(
                ['name' => $name],
                ['abbreviation' => $abbreviation, 'sunat_code' => $sunatCode, 'status' => 1],
            )->id;
        }

        $categories = self::categories($company, array_column($items, 'category'));
        $labs       = self::laboratories($company, array_column($items, 'laboratory'));

        $codes = DocumentSeries::reservar(DocumentSeries::PRODUCTO, count($items), $company->id);
        $now   = now();

        $products = $presentations = $stocks = $movements = $batches = [];
        $withStock = 0;
        $totalUnits = 0.0;

        foreach ($items as $item) {
            $code  = array_shift($codes);
            $stock = round(max(0, (float) $item['stock']), 2);
            $cost  = (float) $item['purchase_price'];
            $price = (float) $item['sale_price'];

            $products[] = [
                'code'              => $code,
                'company_id'        => $company->id,
                'barcode'           => $item['barcode'] !== '' ? $item['barcode'] : null,
                'name'              => $item['name'],
                'description'       => null,
                'category_id'       => $categories[mb_strtolower($item['category'])] ?? null,
                'laboratory_id'     => $item['laboratory'] !== '' ? ($labs[CompanyProductCatalog::labKey($item['laboratory'])] ?? null) : null,
                'active_ingredient' => $item['active_ingredient'] !== '' ? $item['active_ingredient'] : null,
                'purchase_price'    => $cost,
                'unit_sale_price'   => $price,
                'unit_id'           => $units['Unidad'],
                'taxed_product'     => false,
                'igv_affectation'   => Product::IGV_EXONERADO,   // mismo valor por defecto que el formulario de productos
                'requires_recipe'   => (bool) $item['requires_recipe'],
                'controlled_type'   => null,
                'sanitary_registry' => null,
                'location'          => null,
                'status'            => 1,
                'employee_id'       => null,                    // cargado por el sistema, no por un empleado
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            // Presentación principal (la unidad base) y, si el producto las tenía, blíster y caja
            $presentations[] = self::presentation($code, $units['Unidad'], 1, $price, true, $now);
            if ((float) $item['blister_qty'] > 1 && (float) $item['blister_price'] > 0) {
                $presentations[] = self::presentation($code, $units['Blíster'], (float) $item['blister_qty'], (float) $item['blister_price'], false, $now);
            }
            if ((float) $item['pack_qty'] > 1 && (float) $item['pack_price'] > 0) {
                $presentations[] = self::presentation($code, $units['Caja'], (float) $item['pack_qty'], (float) $item['pack_price'], false, $now);
            }

            // Stock de la sede (también con 0, para dejar guardados el mínimo y el máximo)
            $stocks[] = [
                'branch_id'     => $branch->id,
                'product_code'  => $code,
                'stock_actual'  => $stock,
                'stock_minimum' => ($item['stock_min'] ?? '') !== '' ? (int) $item['stock_min'] : null,
                'stock_maximum' => ($item['stock_max'] ?? '') !== '' ? (int) $item['stock_max'] : null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];

            if ($stock > 0) {
                $withStock++;
                $totalUnits += $stock;

                // Kardex: un ajuste de "Inventario inicial", igual que la pantalla de inventario inicial
                $movements[] = [
                    'company_id'     => $company->id,
                    'branch_id'      => $branch->id,
                    'product_code'   => $code,
                    'type'           => 'ajuste',
                    'reference_type' => 'manual',
                    'reference_id'   => null,
                    'quantity'       => $stock,
                    'unit_cost'      => $cost,
                    'balance'        => $stock,
                    'notes'          => self::NOTE,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];

                // Lote solo si el producto trae vencimiento (sin fecha = no vence, como en el inventario inicial)
                if ($item['expiration'] !== '') {
                    $batches[] = [
                        'company_id'         => $company->id,
                        'branch_id'          => $branch->id,
                        'product_code'       => $code,
                        'batch'              => null,
                        'expiration_date'    => $item['expiration'],
                        'quantity_initial'   => $stock,
                        'quantity_remaining' => $stock,
                        'unit_cost'          => $cost,
                        'source_type'        => 'adjustment',
                        'source_id'          => null,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];
                }
            }
        }

        // Inserción en bloques: miles de filas en segundos
        foreach (array_chunk($products, 500) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        // Las filas hijas se armaron con el correlativo (clave temporal 'product_code'):
        // ya insertados los productos, se cambia por su id
        $ids = CompanyProductCatalog::productIds($company, array_column($products, 'code'));
        foreach ([
            'presentations'   => $presentations,
            'branch_stock'    => $stocks,
            'stock_movements' => $movements,
            'stock_batches'   => $batches,
        ] as $table => $rows) {
            foreach (array_chunk(CompanyProductCatalog::withProductIds($rows, $ids), 500) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        }

        return ['products' => count($products), 'with_stock' => $withStock, 'units' => $totalUnits];
    }

    private static function path(string $pack): string
    {
        // Solo el nombre de la carpeta: nada de rutas que salgan de database/data/client_packs
        return base_path(self::DIRECTORY . '/' . basename($pack) . '/productos.csv');
    }

    private static function presentation(string $code, int $unitId, float $amount, float $price, bool $main, $now): array
    {
        return [
            'product_code'      => $code,
            'unit_id'           => $unitId,
            'equivalent_amount' => $amount,
            'sale_price'        => $price,
            'main_presentation' => $main,
            'status'            => 1,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
    }

    /** Categorías por nombre (sin distinguir mayúsculas; crea las que falten). Devuelve [nombre en minúsculas => id] */
    private static function categories(Company $company, array $names): array
    {
        $existing = [];
        foreach ($company->categories()->get(['id', 'name']) as $category) {
            $existing[mb_strtolower($category->name)] = $category->id;
        }

        foreach (array_unique($names) as $name) {
            $key = mb_strtolower($name);
            if ($name === '' || isset($existing[$key])) {
                continue;
            }
            $existing[$key] = $company->categories()->create(['name' => $name, 'icon' => 'fa-capsules', 'status' => 1])->id;
        }

        return $existing;
    }

    /** Laboratorios: reutiliza los de la compañía (comparados con labKey) y crea los que falten. Devuelve [clave => id] */
    private static function laboratories(Company $company, array $names): array
    {
        $existing = [];
        foreach ($company->laboratories()->get(['id', 'name']) as $lab) {
            $existing[CompanyProductCatalog::labKey($lab->name)] = $lab->id;
        }

        foreach (array_unique($names) as $name) {
            if ($name === '') {
                continue;
            }
            $key = CompanyProductCatalog::labKey($name);
            if (!isset($existing[$key])) {
                $existing[$key] = $company->laboratories()->create(['name' => $name, 'status' => 1])->id;
            }
        }

        return $existing;
    }

    /** Filas de productos.csv como arreglos asociativos */
    private static function rows(string $pack): array
    {
        $path = self::path($pack);
        if (!in_array(basename($pack), self::available(), true) || !is_file($path)) {
            throw new RuntimeException("No existe el pack de datos «{$pack}».");
        }

        $file   = fopen($path, 'r');
        $header = fgetcsv($file);
        if ($header !== self::COLUMNS) {
            fclose($file);
            throw new RuntimeException("El pack «{$pack}» no tiene las columnas esperadas de productos.csv.");
        }

        $rows = [];
        while (($line = fgetcsv($file)) !== false) {
            if (count($line) === count($header)) {
                $rows[] = array_combine($header, $line);
            }
        }
        fclose($file);

        return $rows;
    }
}
