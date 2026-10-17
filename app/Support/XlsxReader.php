<?php

namespace App\Support;

use Carbon\Carbon;
use Generator;
use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * Lector mínimo de Excel (.xlsx) sin librerías externas: ZipArchive + XMLReader.
 * Lee la primera hoja fila por fila. Lo usan el catálogo de DIGEMID y la importación de inventario.
 */
class XlsxReader
{
    /**
     * Filas de la primera hoja como arreglos indexados por columna (A=0, B=1...).
     * Las celdas vacías no aparecen en el arreglo.
     */
    public static function rows(string $file): Generator
    {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new RuntimeException('No se pudo abrir el Excel.');
        }

        // Textos compartidos: las celdas de texto guardan solo su índice
        $shared = [];
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml !== false) {
            $reader = new XMLReader();
            $reader->XML($xml);
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'si') {
                    $node = simplexml_load_string($reader->readOuterXml());
                    $shared[] = $node->t !== null && (string) $node->t !== ''
                        ? (string) $node->t
                        : implode('', array_map('strval', $node->xpath('//*[local-name()="t"]')));
                }
            }
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheet === false) {
            throw new RuntimeException('El Excel no tiene hojas.');
        }

        $reader = new XMLReader();
        $reader->XML($sheet);
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'row') {
                continue;
            }
            $row = [];
            foreach (simplexml_load_string($reader->readOuterXml())->c as $cell) {
                $col = 0;
                foreach (str_split(preg_replace('/\d+/', '', (string) $cell['r'])) as $letter) {
                    $col = $col * 26 + (ord($letter) - 64);
                }
                $value = (string) $cell->v;
                if ((string) $cell['t'] === 's') {
                    $value = $shared[(int) $value] ?? '';
                } elseif ((string) $cell['t'] === 'inlineStr') {
                    $value = (string) $cell->is->t;
                }
                $row[$col - 1] = $value;
            }
            yield $row;
        }
    }

    /**
     * Excel guarda las fechas como número de días desde 1899-12-30 (ej. 46022 = 31/12/2025).
     * Devuelve la fecha Y-m-d, o null si el valor no es una fecha serial.
     */
    public static function serialDate(string $value): ?string
    {
        if (!is_numeric($value) || (float) $value < 1 || (float) $value > 200000) {
            return null;
        }

        return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
    }
}
