<?php

namespace App\Services;

use App\Console\Commands\BuildDigemidCatalog;
use App\Models\Company;
use App\Models\DocumentSeries;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Catálogo de productos precargado (opcional al crear una compañía): los medicamentos más comunes
 * del Excel de precios de DIGEMID (database/data/catalogo_medicamentos.csv, ver catalogo:digemid)
 * más productos de botica que no son medicamentos (database/data/catalogo_no_medicamentos.php).
 *
 * Todos nacen con stock 0 (el stock se crea con la primera compra o ajuste). Los medicamentos traen
 * como precio de venta la mediana de DIGEMID (precio referencial por unidad); los demás, sin precio
 * (el POS no deja venderlos hasta que la botica ponga el suyo). Precio de compra en 0.
 *
 * Requiere que la compañía ya tenga su catálogo base (CompanyStarterCatalog): usa sus unidades y
 * categorías, y crea solo las categorías y laboratorios que falten.
 */
class CompanyProductCatalog
{
    public const MEDICINES = 'database/data/catalogo_medicamentos.csv';
    public const OTHERS    = 'database/data/catalogo_no_medicamentos.php';

    /** Categorías que se crean solo al cargar productos (además de las del catálogo base): [nombre, descripción, icono] */
    private const EXTRA_CATEGORIES = [
        'Otros medicamentos'           => ['Medicamentos de otros grupos terapéuticos',             'fa-capsules'],
        'Sistema nervioso'             => ['Ansiolíticos, antidepresivos, anticonvulsivantes y más', 'fa-brain'],
        'Cardiovascular y colesterol'  => ['Colesterol, anticoagulantes y corazón',                  'fa-heart'],
        'Hormonales y anticonceptivos' => ['Tiroides, hormonas y anticonceptivos',                   'fa-venus-mars'],
        'Urológicos y salud sexual'    => ['Próstata, vías urinarias y disfunción eréctil',          'fa-droplet'],
        'Antimicóticos y antivirales'  => ['Hongos, herpes e infecciones virales',                   'fa-virus'],
        'Material médico y curación'   => ['Alcohol, gasas, vendas, jeringas y curación',            'fa-kit-medical'],
        'Dispositivos médicos'         => ['Termómetros, tensiómetros, glucómetros y pruebas',       'fa-stethoscope'],
        'Cuidado personal e higiene'   => ['Higiene, cuidado de la piel y protección solar',         'fa-pump-soap'],
        'Bebé y maternidad'            => ['Pañales, cuidado del bebé y lactancia',                  'fa-baby'],
        'Salud sexual'                 => ['Preservativos, lubricantes y pruebas',                   'fa-heart-circle-check'],
        'Nutrición y bienestar'        => ['Rehidratación, suplementos y bienestar',                 'fa-apple-whole'],
    ];

    /**
     * Carga el catálogo en la compañía. Pensado para correr una sola vez, al crearla.
     * Devuelve ['medicines' => n, 'others' => n]. Debe llamarse dentro de una transacción.
     */
    public static function seed(Company $company): array
    {
        $medicines = self::medicines();
        $others    = self::others();

        $units      = $company->units()->pluck('id', 'name')->all();
        $categories = self::categories($company, array_unique(array_merge(
            array_column($medicines, 'category'),
            array_column($others, 'category'),
        )));
        $labs = self::laboratories($company, $medicines);

        $codes = DocumentSeries::reservar(DocumentSeries::PRODUCTO, count($medicines) + count($others));
        $now   = now();
        $products = [];
        $presentations = [];

        foreach ($medicines as $item) {
            $code = array_shift($codes);
            $products[] = self::row($company, $code, $now, [
                'name'              => $item['name'],
                'category_id'       => $categories[$item['category']] ?? null,
                'laboratory_id'     => $labs[self::labKey($item['laboratory'])] ?? null,
                'active_ingredient' => $item['active_ingredient'] ?: null,
                'unit_sale_price'   => (float) $item['unit_price'],
                'unit_id'           => $units[$item['unit']] ?? $units['Unidad'] ?? null,
                'sanitary_registry' => $item['sanitary_registry'] ?: null,
                'description'       => $item['generic'] ? 'Genérico' : null,
            ]);

            // Empaque con varias unidades: presentación "Caja x N" a precio por unidad × N
            if ((int) $item['pack_amount'] > 1 && isset($units['Caja'])) {
                $presentations[] = [
                    'product_code'      => $code,
                    'unit_id'           => $units['Caja'],
                    'equivalent_amount' => (int) $item['pack_amount'],
                    'sale_price'        => round((float) $item['unit_price'] * (int) $item['pack_amount'], 2),
                    'main_presentation' => false,
                    'status'            => 1,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }
        }

        foreach ($others as $item) {
            $products[] = self::row($company, array_shift($codes), $now, [
                'name'            => $item['name'],
                'category_id'     => $categories[$item['category']] ?? null,
                'unit_sale_price' => 0,       // sin precio: la botica pone el suyo
                'unit_id'         => $units[$item['unit']] ?? $units['Unidad'] ?? null,
            ]);
        }

        // Inserción en bloques: miles de filas en segundos
        foreach (array_chunk($products, 500) as $chunk) {
            DB::table('products')->insert($chunk);
        }
        foreach (array_chunk($presentations, 500) as $chunk) {
            DB::table('presentations')->insert($chunk);
        }

        return ['medicines' => count($medicines), 'others' => count($others)];
    }

    /** Cantidad de productos que se cargarían (para mostrarlo en el alta de compañía) */
    public static function size(): int
    {
        $path = base_path(self::MEDICINES);
        $medicines = is_file($path) ? max(0, count(file($path, FILE_SKIP_EMPTY_LINES)) - 1) : 0;

        return $medicines + count(self::others());
    }

    /** Fila de producto con los valores por defecto del catálogo precargado */
    private static function row(Company $company, string $code, $now, array $data): array
    {
        return array_merge([
            'code'              => $code,
            'company_id'        => $company->id,
            'barcode'           => null,
            'description'       => null,
            'category_id'       => null,
            'laboratory_id'     => null,
            'active_ingredient' => null,
            'purchase_price'    => 0,
            'igv_affectation'   => Product::IGV_EXONERADO,   // mismo valor por defecto que el formulario de productos
            'taxed_product'     => false,
            'requires_recipe'   => false,
            'controlled_type'   => null,
            'sanitary_registry' => null,
            'location'          => null,
            'status'            => 1,
            'employee_id'       => null,                    // cargado por el sistema, no por un empleado
            'created_at'        => $now,
            'updated_at'        => $now,
        ], $data);
    }

    /** Categorías por nombre (crea las que falten). Devuelve [nombre => id] */
    private static function categories(Company $company, array $names): array
    {
        foreach ($names as $name) {
            [$description, $icon] = self::EXTRA_CATEGORIES[$name] ?? [null, 'fa-capsules'];
            $company->categories()->firstOrCreate(['name' => $name], ['description' => $description, 'icon' => $icon, 'status' => 1]);
        }

        return $company->categories()->pluck('id', 'name')->all();
    }

    /**
     * Laboratorios del catálogo: usa los que ya tiene la compañía (comparando sin tildes ni
     * "Laboratorios", "del Perú"...) y crea los que falten. Devuelve [clave normalizada => id].
     */
    private static function laboratories(Company $company, array $medicines): array
    {
        $existing = [];
        foreach ($company->laboratories()->get(['id', 'name']) as $lab) {
            $existing[self::labKey($lab->name)] = $lab->id;
        }

        $countries = [];
        foreach ($medicines as $item) {
            $countries[$item['laboratory']] ??= $item['country'];
        }

        foreach ($countries as $name => $country) {
            $key = self::labKey($name);
            if ($name === '' || isset($existing[$key])) {
                continue;
            }
            $existing[$key] = $company->laboratories()->create(['name' => $name, 'country' => $country ?: null, 'status' => 1])->id;
        }

        return $existing;
    }

    /** "Laboratorios Bago Del Peru" y "Bagó" → "bago" */
    public static function labKey(string $name): string
    {
        $key = Str::of(Str::ascii(mb_strtolower($name)))
            ->replaceMatches('/\b(laboratorios?|lab|del peru|de peru|peru|peruana|sociedad anonima|farmaceutica)\b/', ' ')
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();

        return (string) $key ?: mb_strtolower($name);
    }

    /** Medicamentos del CSV generado por catalogo:digemid */
    private static function medicines(): array
    {
        $path = base_path(self::MEDICINES);
        if (!is_file($path)) {
            throw new RuntimeException('Falta el catálogo de medicamentos. Genéralo con: php artisan catalogo:digemid <Excel de DIGEMID>');
        }

        $file   = fopen($path, 'r');
        $header = fgetcsv($file);
        if ($header !== BuildDigemidCatalog::COLUMNS) {
            fclose($file);
            throw new RuntimeException('El catálogo de medicamentos no tiene el formato esperado. Vuelve a generarlo con catalogo:digemid.');
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

    /** Productos que no son medicamentos: [[name, unit, category], ...] */
    private static function others(): array
    {
        $rows = [];
        foreach (require base_path(self::OTHERS) as $category => $items) {
            foreach ($items as [$name, $unit]) {
                $rows[] = ['name' => $name, 'unit' => $unit, 'category' => $category];
            }
        }

        return $rows;
    }
}
