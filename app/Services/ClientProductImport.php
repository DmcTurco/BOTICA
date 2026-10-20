<?php

namespace App\Services;

use App\Models\Product;
use App\Support\XlsxReader;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Lee y valida el archivo de productos de un cliente que migra de otro sistema (Excel .xlsx o CSV)
 * antes de cargarlo con CompanyClientDataPack::seedRows. Nada se guarda aquí: devuelve las filas
 * limpias, los errores (bloquean la carga) y los avisos (no la bloquean).
 *
 * Las columnas se ubican por el nombre del encabezado (sin importar el orden), así que basta con
 * llenar el modelo que se descarga del panel. También se aceptan los nombres en inglés del pack.
 */
class ClientProductImport
{
    /** Columnas del modelo descargable: [encabezado en español, clave interna] */
    public const TEMPLATE = [
        ['codigo_barras', 'barcode'],
        ['producto', 'name'],
        ['categoria', 'category'],
        ['laboratorio', 'laboratory'],
        ['principio_activo', 'active_ingredient'],
        ['stock', 'stock'],
        ['precio_compra', 'purchase_price'],
        ['precio_venta', 'sale_price'],
        ['stock_minimo', 'stock_min'],
        ['stock_maximo', 'stock_max'],
        ['vencimiento', 'expiration'],
        ['requiere_receta', 'requires_recipe'],
        ['cantidad_caja', 'pack_qty'],
        ['precio_caja', 'pack_price'],
        ['cantidad_blister', 'blister_qty'],
        ['precio_blister', 'blister_price'],
    ];

    /** Otros nombres que se aceptan para una columna (además del español y del inglés de TEMPLATE) */
    private const ALIASES = [
        'nombre' => 'name', 'costo' => 'purchase_price', 'costo_unitario' => 'purchase_price', 'precio' => 'sale_price',
        'codigo' => 'barcode', 'cod_barras' => 'barcode',
    ];

    /** Las filas cuyo producto empieza con esto se ignoran (es el ejemplo del modelo) */
    public const EXAMPLE_PREFIX = 'EJEMPLO';

    public const MAX_ROWS = 20000;

    /** Fila de ejemplo del modelo descargable (en el orden de TEMPLATE) */
    public static function exampleRow(): array
    {
        return ['7750000000011', 'EJEMPLO - Paracetamol 500 mg x 100 (borra esta fila)', 'Analgésicos', 'Medifarma', 'Paracetamol',
            '24', '0.20', '0.50', '3', '10', '31/12/2027', 'No', '100', '40.00', '10', '4.00'];
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>, warnings: array<int, string>, skipped: int}
     */
    public static function parse(string $path, string $extension, ?int $companyId = null): array
    {
        $lines = strtolower($extension) === 'xlsx'
            ? iterator_to_array(XlsxReader::rows($path), false)
            : self::csvLines($path);

        $header = null;
        $rows = $errors = $warnings = [];
        $skipped = 0;
        $seen = [];   // código de barras => fila donde apareció

        foreach ($lines as $i => $line) {
            $number = $i + 1;

            if ($header === null) {
                $map = self::headerMap($line);
                if (isset($map['name'])) {
                    foreach (['stock', 'purchase_price', 'sale_price'] as $required) {
                        if (!isset($map[$required])) {
                            throw new RuntimeException('Al archivo le falta la columna «' . self::spanish($required) . '». Descarga el modelo y úsalo tal cual.');
                        }
                    }
                    $header = $map;
                }
                continue;
            }

            // XlsxReader no trae las celdas vacías: se rellenan para que las posiciones coincidan
            $max   = max(array_merge([-1], array_keys($line)));
            $cells = [];
            for ($c = 0; $c <= $max; $c++) {
                $cells[$c] = trim((string) ($line[$c] ?? ''));
            }
            $get = fn (string $key) => isset($header[$key]) ? ($cells[$header[$key]] ?? '') : '';

            // Filas vacías y la fila de ejemplo del modelo
            if (!array_filter($cells, fn ($v) => $v !== '')) {
                continue;
            }
            if (Str::startsWith(Str::upper($get('name')), self::EXAMPLE_PREFIX)) {
                $skipped++;
                continue;
            }

            if (count($rows) + count($errors) >= self::MAX_ROWS) {
                throw new RuntimeException('El archivo tiene más de ' . number_format(self::MAX_ROWS) . ' productos. Divídelo en varios.');
            }

            $before = count($errors);
            $label  = "Fila {$number}";
            $name   = preg_replace('/\s+/u', ' ', $get('name'));

            if ($name === '') {
                $errors[] = "{$label}: falta el nombre del producto.";
                continue;
            }
            $label .= " ({$name})";
            if (mb_strlen($name) > 150) {
                $errors[] = "{$label}: el nombre pasa de 150 caracteres.";
            }

            // Textos con su largo máximo (el de la base de datos)
            $text = [];
            foreach (['category' => ['categoría', 50], 'laboratory' => ['laboratorio', 100], 'active_ingredient' => ['principio activo', 100]] as $key => [$what, $max]) {
                $value = preg_replace('/\s+/u', ' ', $get($key));
                if (mb_strlen($value) > $max) {
                    $errors[] = "{$label}: el {$what} pasa de {$max} caracteres.";
                }
                $text[$key] = $value;
            }

            // Código de barras: letras, números y guiones; no se repite en el archivo ni en la compañía
            $barcode = $get('barcode');
            if ($barcode !== '') {
                if (!preg_match('/^[A-Za-z0-9\-]{1,30}$/', $barcode)) {
                    $errors[] = "{$label}: el código de barras «{$barcode}» no es válido (solo letras, números y guiones, hasta 30). Si lo ves como 7.75E+12, formatea esa columna como Texto en Excel.";
                } elseif (isset($seen[$barcode])) {
                    $errors[] = "{$label}: el código de barras «{$barcode}» ya está en la fila {$seen[$barcode]}.";
                } else {
                    $seen[$barcode] = $number;
                }
            }

            // Números
            $num = [];
            foreach (['stock' => 'stock', 'purchase_price' => 'precio de compra', 'sale_price' => 'precio de venta'] as $key => $what) {
                $value = self::number($get($key));
                if ($value === false || ($value !== null && $value < 0)) {
                    $errors[] = "{$label}: «{$get($key)}» no es un número válido en {$what} (no puede ser negativo).";
                    $value = 0;
                }
                $num[$key] = $value ?? 0;
            }
            foreach (['stock_min' => 'stock mínimo', 'stock_max' => 'stock máximo'] as $key => $what) {
                $value = self::number($get($key));
                if ($value === false || ($value !== null && $value < 0)) {
                    $errors[] = "{$label}: «{$get($key)}» no es válido en {$what}.";
                    $value = null;
                }
                $num[$key] = $value === null ? '' : (int) round($value);
            }

            // Presentaciones: cantidad y precio van juntos
            foreach (['pack' => 'caja', 'blister' => 'blíster'] as $key => $what) {
                $qty   = self::number($get("{$key}_qty"));
                $price = self::number($get("{$key}_price"));
                if ($qty === false || $price === false || ($qty !== null && $qty < 0) || ($price !== null && $price < 0)) {
                    $errors[] = "{$label}: la cantidad o el precio de la {$what} no son números válidos.";
                    $qty = $price = null;
                } elseif (($qty !== null && $qty > 0) xor ($price !== null && $price > 0)) {
                    $errors[] = "{$label}: la {$what} necesita cantidad y precio a la vez (o ninguno de los dos).";
                    $qty = $price = null;
                } elseif ($qty !== null && $qty > 0 && $qty <= 1) {
                    $warnings[] = "{$label}: la cantidad de la {$what} es 1 o menos, no se crea esa presentación.";
                    $qty = $price = null;
                }
                $num["{$key}_qty"]   = $qty ?: '';
                $num["{$key}_price"] = $price ?: '';
            }

            // Vencimiento
            $expiration = '';
            if ($get('expiration') !== '') {
                $expiration = self::date($get('expiration')) ?? '';
                if ($expiration === '') {
                    $errors[] = "{$label}: «{$get('expiration')}» no es una fecha válida (usa dd/mm/aaaa).";
                } elseif ($num['stock'] <= 0) {
                    $expiration = '';   // sin stock no hay lote que vencer
                }
            }

            if ($num['sale_price'] <= 0) {
                $warnings[] = "{$label}: no tiene precio de venta; el punto de venta no deja venderlo hasta que lo pongas.";
            }
            if ($num['purchase_price'] > 0 && $num['sale_price'] > 0 && $num['sale_price'] < $num['purchase_price']) {
                $warnings[] = "{$label}: el precio de venta ({$num['sale_price']}) es menor que el de compra ({$num['purchase_price']}).";
            }

            if (count($errors) > $before) {
                continue;
            }

            $rows[] = [
                'line'              => $number,   // fila del archivo, para ubicarlo en la vista previa
                'barcode'           => $barcode,
                'name'              => $name,
                'category'          => $text['category'] !== '' ? $text['category'] : 'Sin categoría',
                'laboratory'        => $text['laboratory'],
                'active_ingredient' => $text['active_ingredient'],
                'stock'             => $num['stock'],
                'purchase_price'    => $num['purchase_price'],
                'sale_price'        => $num['sale_price'],
                'stock_min'         => $num['stock_min'],
                'stock_max'         => $num['stock_max'],
                'expiration'        => $expiration,
                'requires_recipe'   => self::yes($get('requires_recipe')) ? 1 : 0,
                'pack_qty'          => $num['pack_qty'],
                'pack_price'        => $num['pack_price'],
                'blister_qty'       => $num['blister_qty'],
                'blister_price'     => $num['blister_price'],
            ];
        }

        if ($header === null) {
            throw new RuntimeException('No se encontró la fila de encabezados (al menos la columna «producto»). Descarga el modelo y úsalo tal cual.');
        }

        // Con compañía ya elegida se revisan sus códigos de barras (sin ella, se revisan al registrar)
        if ($companyId !== null) {
            $errors = array_merge($errors, self::conflicts($rows, $companyId));
        }

        return ['rows' => $rows, 'errors' => $errors, 'warnings' => $warnings, 'skipped' => $skipped];
    }

    /**
     * Códigos de barras de las filas que la compañía ya tiene (incluye eliminados: el índice único también los cuenta).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, string>  un mensaje por fila repetida
     */
    public static function conflicts(array $rows, int $companyId): array
    {
        $codes = array_values(array_filter(array_column($rows, 'barcode')));
        if (!$codes) {
            return [];
        }

        $existing = [];
        foreach (array_chunk($codes, 1000) as $chunk) {
            $existing = array_merge($existing, Product::withTrashed()->where('company_id', $companyId)->whereIn('barcode', $chunk)->pluck('barcode')->all());
        }
        $existing = array_flip($existing);

        $messages = [];
        foreach ($rows as $row) {
            if ($row['barcode'] !== '' && isset($existing[$row['barcode']])) {
                $messages[] = "Fila {$row['line']} («{$row['name']}»): el código de barras {$row['barcode']} ya existe en esta compañía.";
            }
        }

        return $messages;
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** [clave interna => posición de la columna] a partir del encabezado, o [] si no se reconoce */
    private static function headerMap(array $line): array
    {
        $known = [];
        foreach (self::TEMPLATE as [$es, $key]) {
            $known[$es] = $key;
            $known[$key] = $key;
        }
        $known += self::ALIASES;

        $map = [];
        foreach ($line as $position => $title) {
            $slug = Str::slug((string) $title, '_');
            if (isset($known[$slug]) && !isset($map[$known[$slug]])) {
                $map[$known[$slug]] = (int) $position;
            }
        }

        return $map;
    }

    private static function spanish(string $key): string
    {
        foreach (self::TEMPLATE as [$es, $internal]) {
            if ($internal === $key) {
                return $es;
            }
        }

        return $key;
    }

    /** null = vacío, false = no es número, float = valor ("1,5" y "1.5" valen igual) */
    private static function number(string $value): float|false|null
    {
        $value = str_replace(' ', '', $value);
        if ($value === '') {
            return null;
        }
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : false;
    }

    private static function yes(string $value): bool
    {
        return in_array(Str::lower(Str::ascii($value)), ['1', 'si', 's', 'true', 'x', 'yes'], true);
    }

    /** Fecha del Excel: número de serie, dd/mm/aaaa, dd-mm-aaaa o aaaa-mm-dd → Y-m-d */
    private static function date(string $value): ?string
    {
        if ($serial = XlsxReader::serialDate($value)) {
            return $serial;
        }

        if (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $value, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }

        // "31/12/2027", "2/02/2023", "5-5-24": día/mes/año
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{2}|\d{4})$#', $value, $m)) {
            $year = (int) $m[3] < 100 ? 2000 + (int) $m[3] : (int) $m[3];
            if (checkdate((int) $m[2], (int) $m[1], $year)) {
                return sprintf('%04d-%02d-%02d', $year, $m[2], $m[1]);
            }
        }

        return null;
    }

    /** Filas de un CSV: quita el BOM, pasa de Windows-1252 a UTF-8 si hace falta y detecta ; o , como separador */
    private static function csvLines(string $path): array
    {
        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');   // Excel en Windows guarda así el "CSV"
        }

        $first = strtok($content, "\n") ?: '';
        $sep   = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $lines = [];
        while (($row = fgetcsv($stream, 0, $sep)) !== false) {
            $lines[] = $row;
        }
        fclose($stream);

        return $lines;
    }
}
