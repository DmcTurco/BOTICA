<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSeries extends Model
{
    protected $table = 'document_series';

    protected $fillable = [
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
    const PRODUCTO      = 'PRODUCTO';
    const PROVEEDOR     = 'PROVEEDOR';
    const CLIENTE       = 'CLIENTE';
    const COMPRA        = 'COMPRA';
    const CREDITO_FIADO = 'CREDITO_FIADO';
    const CIERRE_CAJA   = 'CIERRE_CAJA';

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
     * Si la serie activa alcanzó su límite, la cierra automáticamente
     * y activa la siguiente (B001 → B002, CMP → no rota — solo SUNAT rota).
     */
    public static function siguiente(string $typeCode): string
    {
        $serie = self::where('type_code', $typeCode)
            ->where('active', true)
            ->lockForUpdate()
            ->first();

        if (!$serie) {
            throw new \RuntimeException(
                "No hay serie activa para el tipo de documento: {$typeCode}."
            );
        }

        $limite      = $serie->digits === 8 ? self::LIMITE_CORRELATIVO : (10 ** $serie->digits) - 1;
        $nuevoNumero = $serie->current_number + 1;

        // ── Límite alcanzado: rotar a la siguiente serie ──────────
        if ($nuevoNumero > $limite) {
            $serie->update(['active' => false]);

            $siguienteSerie = self::calcularSiguienteSerie($serie->series);

            $serie = self::create([
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
