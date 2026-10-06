<?php

namespace App\Services\Sunat;

use InvalidArgumentException;

/**
 * Calcula el IGV y los totales de una venta según la afectación de cada producto
 * (catálogo SUNAT 07): 10 gravado, 20 exonerado, 30 inafecto.
 *
 * Los precios de venta del sistema son valores SIN IGV; el IGV (18%) se suma
 * solo a las líneas gravadas. El IGV se redondea por línea y luego se suma.
 */
class TaxCalculator
{
    const IGV_RATE = 0.18;

    const GRAVADO   = '10';
    const EXONERADO = '20';
    const INAFECTO  = '30';

    /**
     * @param  array<int, array{code: string, price: float|int|string, qty: float|int|string}>  $items
     * @param  array<string, string>  $affectations  código de producto => afectación (10, 20 o 30)
     * @return array{
     *     lines: array<int, array{code: string, base: float, igv: float, affectation: string}>,
     *     taxable: float, exonerated: float, unaffected: float,
     *     subtotal: float, igv: float, total: float
     * }
     */
    public function calculate(array $items, array $affectations): array
    {
        $lines = [];
        $taxable = $exonerated = $unaffected = $igv = 0.0;

        foreach ($items as $index => $item) {
            $code = $item['code'];

            if (!isset($affectations[$code])) {
                throw new InvalidArgumentException("Producto sin afectación de IGV: {$code}");
            }

            $affectation = $affectations[$code];
            $base        = round((float) $item['price'] * (float) $item['qty'], 2);
            $lineIgv     = $affectation === self::GRAVADO ? round($base * self::IGV_RATE, 2) : 0.0;

            match ($affectation) {
                self::GRAVADO   => $taxable    += $base,
                self::EXONERADO => $exonerated += $base,
                self::INAFECTO  => $unaffected += $base,
                default         => throw new InvalidArgumentException("Afectación de IGV no válida: {$affectation}"),
            };

            $igv += $lineIgv;

            $lines[$index] = [
                'code'        => $code,
                'base'        => $base,
                'igv'         => $lineIgv,
                'affectation' => $affectation,
            ];
        }

        $subtotal = round($taxable + $exonerated + $unaffected, 2);

        return [
            'lines'      => $lines,
            'taxable'    => round($taxable, 2),
            'exonerated' => round($exonerated, 2),
            'unaffected' => round($unaffected, 2),
            'subtotal'   => $subtotal,
            'igv'        => round($igv, 2),
            'total'      => round($subtotal + $igv, 2),
        ];
    }
}
