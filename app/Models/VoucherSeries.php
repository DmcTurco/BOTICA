<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VoucherSeries extends Model
{
    protected $table = 'voucher_series';

    protected $fillable = [
        'voucher_type',
        'series',
        'current_number',
        'active',
    ];

    protected $casts = [
        'active'         => 'boolean',
        'current_number' => 'integer',
    ];

    // Etiquetas legibles del tipo de comprobante
    const TIPO_BOLETA  = 1;
    const TIPO_FACTURA = 2;
    const TIPO_NOTA    = 3;

    /**
     * Límite de correlativo según SUNAT (8 dígitos).
     */
    const LIMITE_CORRELATIVO = 99_999_999;

    /**
     * Genera el siguiente número de comprobante de forma atómica.
     * Debe llamarse DENTRO de una transacción DB existente.
     *
     * Retorna el string formateado según SUNAT: "B001-00000001"
     *
     * Si la serie activa alcanzó el límite de 99,999,999, la cierra
     * automáticamente y activa la siguiente (B001 → B002, F001 → F002, etc.).
     *
     * Lanza una excepción si no existe serie activa para el tipo.
     */
    public static function generarNumero(int $voucherType): string
    {
        // Bloquear la fila para evitar duplicados en concurrencia
        $serie = self::where('voucher_type', $voucherType)
            ->where('active', true)
            ->lockForUpdate()
            ->first();

        if (!$serie) {
            throw new \RuntimeException(
                'No hay serie activa para el tipo de comprobante ' . $voucherType . '. Contacta al administrador.'
            );
        }

        $nuevoNumero = $serie->current_number + 1;

        // ── Límite SUNAT alcanzado: rotar a la siguiente serie ────────
        if ($nuevoNumero > self::LIMITE_CORRELATIVO) {
            // Desactivar la serie actual
            $serie->update(['active' => false]);

            // Calcular la siguiente serie: B001 → B002, B009 → B010, etc.
            $siguienteSerie = self::calcularSiguienteSerie($serie->series);

            // Crear la nueva serie comenzando en 0
            $serie = self::create([
                'voucher_type'   => $voucherType,
                'series'         => $siguienteSerie,
                'current_number' => 0,
                'active'         => true,
            ]);

            $nuevoNumero = 1;
        }

        $serie->update(['current_number' => $nuevoNumero]);

        // Formato SUNAT: SERIE-CORRELATIVO (8 dígitos con ceros a la izquierda)
        return $serie->series . '-' . str_pad($nuevoNumero, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Calcula la siguiente serie incrementando el sufijo numérico.
     * Ejemplos: B001 → B002 · B009 → B010 · B099 → B100 · NV01 → NV02
     */
    private static function calcularSiguienteSerie(string $serieActual): string
    {
        // Separar la parte alfabética del sufijo numérico
        preg_match('/^([A-Za-z]+)(\d+)$/', $serieActual, $matches);

        if (!$matches) {
            throw new \RuntimeException("Formato de serie no reconocido: {$serieActual}");
        }

        $prefijo        = $matches[1];                    // "B", "F", "NV"
        $numero         = (int) $matches[2];              // 1, 2, 99...
        $longitud       = strlen($matches[2]);            // mantener el padding original
        $siguienteNumero = $numero + 1;

        // Verificar que el nuevo número no supere la longitud de dígitos del sufijo
        if (strlen((string) $siguienteNumero) > $longitud) {
            throw new \RuntimeException(
                "La serie {$serieActual} no puede rotarse: el sufijo numérico se desbordó. Crea una nueva serie manualmente."
            );
        }

        return $prefijo . str_pad($siguienteNumero, $longitud, '0', STR_PAD_LEFT);
    }

    /**
     * Devuelve la etiqueta del tipo de comprobante.
     */
    public static function etiquetaTipo(int $tipo): string
    {
        return match($tipo) {
            self::TIPO_BOLETA  => 'Boleta',
            self::TIPO_FACTURA => 'Factura',
            self::TIPO_NOTA    => 'Nota de Venta',
            default            => 'Comprobante',
        };
    }
}
