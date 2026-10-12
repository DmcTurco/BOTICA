<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga de reportes como CSV que Excel abre directamente (UTF-8 con BOM, separador coma).
 */
class CsvExport
{
    /**
     * @param  string                  $filename  sin extensión
     * @param  array<int, string>      $headers   títulos de columna
     * @param  iterable<array<mixed>>  $rows      una fila por elemento (mismo orden que $headers)
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $filename) . '.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");               // BOM: Excel reconoce las tildes y la ñ
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($value) => self::cell($value), $row));
            }

            fclose($out);
        }, $safeName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Convierte un valor a celda: los decimales sin separador de miles y las fechas en dd/mm/aaaa.
     * Un texto que empiece con =, +, - o @ se antepone con comilla para que Excel no lo ejecute como fórmula.
     */
    private static function cell(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }
        if (is_float($value)) {
            return number_format($value, 2, '.', '');
        }
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        $text = (string) ($value ?? '');

        return $text !== '' && str_contains('=+-@', $text[0]) && !is_numeric($text) ? "'" . $text : $text;
    }
}
