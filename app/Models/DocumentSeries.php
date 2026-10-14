<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSeries extends Model
{
    protected $table = 'document_series';

    protected $fillable = [
        'company_id',
        'branch_id',
        'type_code',
        'name',
        'series',
        'current_number',
        'digits',
        'active',
    ];

    protected $casts = [
        'current_number' => 'integer',
        'digits'         => 'integer',
        'active'         => 'boolean',
    ];

    // ── Códigos de tipo de documento del sistema ──────────────────
    const BOLETA        = 'BOLETA';
    const FACTURA       = 'FACTURA';
    const NOTA_VENTA    = 'NOTA_VENTA';
    const NOTA_CREDITO  = 'NOTA_CREDITO';
    const NOTA_CREDITO_BOLETA  = 'NOTA_CREDITO_BOLETA';  // serie que empieza con B (anula boletas)
    const NOTA_CREDITO_FACTURA = 'NOTA_CREDITO_FACTURA'; // serie que empieza con F (anula facturas)
    const PRODUCTO      = 'PRODUCTO';
    const PROVEEDOR     = 'PROVEEDOR';
    const CLIENTE       = 'CLIENTE';
    const COMPRA        = 'COMPRA';
    const CREDITO_FIADO = 'CREDITO_FIADO';
    const CIERRE_CAJA   = 'CIERRE_CAJA';

    /**
     * Comprobantes cuya serie y correlativo son propios de cada sede
     * (SUNAT exige series por RUC y por establecimiento).
     * prefix = letras iniciales de la serie · width = dígitos del sufijo (B001, NV01).
     */
    const PER_BRANCH = [
        self::BOLETA     => ['prefix' => 'B',  'width' => 3, 'name' => 'Boleta de Venta'],
        self::FACTURA    => ['prefix' => 'F',  'width' => 3, 'name' => 'Factura'],
        self::NOTA_VENTA => ['prefix' => 'NV', 'width' => 2, 'name' => 'Nota de Venta'],
        self::NOTA_CREDITO_BOLETA  => ['prefix' => 'BC', 'width' => 2, 'name' => 'Nota de Crédito (Boleta)'],
        self::NOTA_CREDITO_FACTURA => ['prefix' => 'FC', 'width' => 2, 'name' => 'Nota de Crédito (Factura)'],
    ];

    /**
     * Correlativos internos globales (sin sede ni compañía): P-000001, C-000001...
     */
    const GLOBAL = [
        self::PRODUCTO      => ['series' => 'P',   'name' => 'Producto'],
        self::PROVEEDOR     => ['series' => 'X',   'name' => 'Proveedor'],
        self::CLIENTE       => ['series' => 'C',   'name' => 'Cliente'],
        self::COMPRA        => ['series' => 'CMP', 'name' => 'Orden de Compra'],
        self::CREDITO_FIADO => ['series' => 'CRD', 'name' => 'Crédito Fiado'],
        self::CIERRE_CAJA   => ['series' => 'CIR', 'name' => 'Cierre de Caja'],
    ];

    // Límite de correlativo según SUNAT (8 dígitos)
    const LIMITE_CORRELATIVO = 99_999_999;

    /**
     * Mapeo de voucher_type entero (orders) → type_code string.
     */
    const VOUCHER_TYPE_MAP = [
        1 => self::BOLETA,
        2 => self::FACTURA,
        3 => self::NOTA_VENTA,
    ];

    /**
     * Genera el siguiente número de documento de forma atómica.
     * Debe llamarse DENTRO de una transacción DB existente.
     *
     * Ejemplos:
     *   DocumentSeries::siguiente('BOLETA')   → "B001-00000001"
     *   DocumentSeries::siguiente('PRODUCTO') → "P-000001"
     *   DocumentSeries::siguiente('COMPRA')   → "CMP-000001"
     *
     * Los comprobantes de venta (BOLETA, FACTURA, NOTA_VENTA) llevan serie y correlativo
     * propios de cada sede: hay que pasar el $branchId de la sede que vende.
     * Los demás correlativos (PRODUCTO, CLIENTE...) son globales y no llevan sede.
     *
     * Si la serie activa alcanzó su límite, la cierra automáticamente
     * y activa la siguiente (B001 → B002, CMP → no rota — solo SUNAT rota).
     */
    public static function siguiente(string $typeCode, ?int $branchId = null): string
    {
        if (isset(self::PER_BRANCH[$typeCode]) && $branchId === null) {
            throw new \RuntimeException("El comprobante {$typeCode} requiere la sede que lo emite.");
        }

        $findSerie = fn () => self::where('type_code', $typeCode)
            ->where('active', true)
            ->when(
                isset(self::PER_BRANCH[$typeCode]),
                fn ($q) => $q->where('branch_id', $branchId),
                fn ($q) => $q->whereNull('branch_id')
            )
            ->lockForUpdate()
            ->first();

        $serie = $findSerie();

        // Correlativo interno global faltante (no se corrió el seeder): se crea con sus valores fijos
        if (!$serie && isset(self::GLOBAL[$typeCode])
            && !self::where('type_code', $typeCode)->whereNull('branch_id')->exists()) {
            self::crearSeriesGlobales();
            $serie = $findSerie();
        }

        if (!$serie) {
            throw new \RuntimeException(isset(self::PER_BRANCH[$typeCode])
                ? "No hay serie activa para este comprobante en la sede ({$typeCode}). Pide a la empresa que genere las series de la sede en Sedes."
                : "No hay correlativo activo para {$typeCode}. Activa uno en Series."
            );
        }

        $limite      = $serie->digits === 8 ? self::LIMITE_CORRELATIVO : (10 ** $serie->digits) - 1;
        $nuevoNumero = $serie->current_number + 1;

        // ── Límite alcanzado: rotar a la siguiente serie ──────────
        if ($nuevoNumero > $limite) {
            $serie->update(['active' => false]);

            // La nueva serie no puede repetir una ya usada por otra sede de la misma compañía
            $siguienteSerie = self::calcularSiguienteSerie($serie->series);
            while (self::where('company_id', $serie->company_id)
                ->where('type_code', $typeCode)
                ->where('series', $siguienteSerie)
                ->exists()) {
                $siguienteSerie = self::calcularSiguienteSerie($siguienteSerie);
            }

            $serie = self::create([
                'company_id'     => $serie->company_id,
                'branch_id'      => $serie->branch_id,
                'type_code'      => $typeCode,
                'name'           => $serie->name,
                'series'         => $siguienteSerie,
                'current_number' => 0,
                'digits'         => $serie->digits,
                'active'         => true,
            ]);

            $nuevoNumero = 1;
        }

        $serie->update(['current_number' => $nuevoNumero]);

        // Formato: SERIE-CORRELATIVO con ceros a la izquierda
        return $serie->series . '-' . str_pad($nuevoNumero, $serie->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Crea las series de comprobantes de una sede (boleta, factura y nota de venta).
     * Cada serie toma el siguiente número libre de la compañía (B001, B002...).
     * Es idempotente: si la sede ya tiene serie de un tipo, no crea otra.
     */
    public static function crearSeriesParaSede(Branch $branch): void
    {
        foreach (self::PER_BRANCH as $typeCode => $format) {
            $exists = self::where('branch_id', $branch->id)->where('type_code', $typeCode)->exists();

            if ($exists) {
                continue;
            }

            $max = 0;
            foreach (self::where('company_id', $branch->company_id)->where('type_code', $typeCode)->pluck('series') as $series) {
                if (preg_match('/^' . $format['prefix'] . '(\d+)$/', $series, $m)) {
                    $max = max($max, (int) $m[1]);
                }
            }

            self::create([
                'company_id'     => $branch->company_id,
                'branch_id'      => $branch->id,
                'type_code'      => $typeCode,
                'name'           => $format['name'],
                'series'         => $format['prefix'] . str_pad($max + 1, $format['width'], '0', STR_PAD_LEFT),
                'current_number' => 0,
                'digits'         => 8,
                'active'         => true,
            ]);
        }
    }

    /**
     * Crea los correlativos internos globales que falten (producto, cliente, compra...).
     * Es idempotente: solo inserta los tipos que aún no existen.
     *
     * @return int Cantidad de correlativos creados
     */
    public static function crearSeriesGlobales(): int
    {
        $created = 0;

        foreach (self::GLOBAL as $typeCode => $format) {
            $exists = self::where('type_code', $typeCode)->whereNull('branch_id')->exists();

            if ($exists) {
                continue;
            }

            self::create([
                'type_code'      => $typeCode,
                'name'           => $format['name'],
                'series'         => $format['series'],
                'current_number' => 0,
                'digits'         => 6,
                'active'         => true,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Tipos de comprobante de la sede que no tienen ninguna serie registrada.
     *
     * @return array<int, string> type_codes faltantes
     */
    public static function tiposFaltantesDeSede(Branch $branch): array
    {
        $existing = $branch->relationLoaded('documentSeries')
            ? $branch->documentSeries->pluck('type_code')->all()
            : $branch->documentSeries()->pluck('type_code')->all();

        return array_values(array_diff(array_keys(self::PER_BRANCH), $existing));
    }

    /**
     * Convierte el voucher_type entero de orders al type_code correspondiente.
     * Útil en OrderController para no hardcodear strings.
     */
    public static function typeCodeDesdeVoucher(int $voucherType): string
    {
        $map = self::VOUCHER_TYPE_MAP;

        if (!isset($map[$voucherType])) {
            throw new \RuntimeException("Tipo de comprobante no reconocido: {$voucherType}");
        }

        return $map[$voucherType];
    }

    /**
     * Calcula la siguiente serie incrementando el sufijo numérico.
     * Ejemplos: B001 → B002 · F009 → F010 · NV01 → NV02
     * Series sin sufijo numérico (P, C) no rotan — lanzan excepción.
     */
    private static function calcularSiguienteSerie(string $serieActual): string
    {
        preg_match('/^([A-Za-z]+)(\d+)$/', $serieActual, $matches);

        if (!$matches) {
            throw new \RuntimeException(
                "La serie '{$serieActual}' no puede rotarse automáticamente. Crea la siguiente serie manualmente."
            );
        }

        $prefijo         = $matches[1];
        $numero          = (int) $matches[2];
        $longitud        = strlen($matches[2]);
        $siguienteNumero = $numero + 1;

        if (strlen((string) $siguienteNumero) > $longitud) {
            throw new \RuntimeException(
                "La serie '{$serieActual}' no puede rotarse: el sufijo numérico se desbordó. Crea la siguiente serie manualmente."
            );
        }

        return $prefijo . str_pad($siguienteNumero, $longitud, '0', STR_PAD_LEFT);
    }
}
