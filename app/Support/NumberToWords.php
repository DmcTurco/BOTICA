<?php

namespace App\Support;

/**
 * Convierte importes a letras en español (leyenda 1000 de SUNAT: "SON ... SOLES").
 */
class NumberToWords
{
    private const UNITS = [
        '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ',
        'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
        'VEINTE', 'VEINTIUNO', 'VEINTIDOS', 'VEINTITRES', 'VEINTICUATRO', 'VEINTICINCO',
        'VEINTISEIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE',
    ];

    private const TENS = [3 => 'TREINTA', 4 => 'CUARENTA', 5 => 'CINCUENTA', 6 => 'SESENTA', 7 => 'SETENTA', 8 => 'OCHENTA', 9 => 'NOVENTA'];

    private const HUNDREDS = [
        1 => 'CIENTO', 2 => 'DOSCIENTOS', 3 => 'TRESCIENTOS', 4 => 'CUATROCIENTOS', 5 => 'QUINIENTOS',
        6 => 'SEISCIENTOS', 7 => 'SETECIENTOS', 8 => 'OCHOCIENTOS', 9 => 'NOVECIENTOS',
    ];

    /**
     * Ejemplo: 120.5 → "SON CIENTO VEINTE CON 50/100 SOLES".
     */
    public static function soles(float $amount): string
    {
        $amount  = round($amount, 2);
        $integer = (int) floor($amount);
        $cents   = (int) round(($amount - $integer) * 100);

        // "UNO" pasa a "UN" antes de CON (SON UN CON 00/100 SOLES · SON VEINTIUN CON 90/100 SOLES)
        $words = preg_replace('/UNO$/', 'UN', self::integer($integer));

        return 'SON ' . $words . ' CON ' . str_pad((string) $cents, 2, '0', STR_PAD_LEFT) . '/100 SOLES';
    }

    /** Número entero en letras (0 a 999 999 999) */
    public static function integer(int $n): string
    {
        if ($n === 0) {
            return 'CERO';
        }

        $millions  = intdiv($n, 1_000_000);
        $thousands = intdiv($n % 1_000_000, 1000);
        $rest      = $n % 1000;

        $text = [];

        if ($millions > 0) {
            $text[] = $millions === 1 ? 'UN MILLON' : self::hundreds($millions, true) . ' MILLONES';
        }

        if ($thousands > 0) {
            $text[] = $thousands === 1 ? 'MIL' : self::hundreds($thousands, true) . ' MIL';
        }

        if ($rest > 0) {
            $text[] = self::hundreds($rest);
        }

        return implode(' ', $text);
    }

    /** Grupo de tres cifras (1-999); $apocope convierte "UNO" en "UN" ante MIL/MILLONES */
    private static function hundreds(int $n, bool $apocope = false): string
    {
        if ($n === 100) {
            return 'CIEN';
        }

        $text = [];

        if ($n >= 100) {
            $text[] = self::HUNDREDS[intdiv($n, 100)];
            $n %= 100;
        }

        if ($n > 0) {
            if ($n < 30) {
                $text[] = self::UNITS[$n];
            } else {
                $tens = self::TENS[intdiv($n, 10)];
                $unit = $n % 10;
                $text[] = $unit > 0 ? $tens . ' Y ' . self::UNITS[$unit] : $tens;
            }
        }

        $words = implode(' ', $text);

        if ($apocope) {
            $words = preg_replace('/UNO$/', 'UN', $words);
            $words = preg_replace('/VEINTIUNO$/', 'VEINTIUN', $words);
        }

        return $words;
    }
}
