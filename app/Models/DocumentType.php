<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = 'document_types';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'description',
        'digits',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'digits'     => 'integer',
        'active'     => 'boolean',
        'sort_order' => 'integer',
    ];

    // Código SUNAT para DNI
    const DNI = 1;

    // Código SUNAT para Carnet de Extranjería
    const CE = 2;

    // Código SUNAT para RUC
    const RUC = 3;

    // Código SUNAT para Pasaporte
    const PASAPORTE = 4;

    // Sin documento / varios
    const SIN_DOCUMENTO = 5;

    /**
     * Tipos de documento de identidad base (SUNAT). El id es fijo: las constantes de arriba dependen de él.
     */
    const BASE = [
        ['id' => 1, 'code' => '1', 'name' => 'DNI',                  'description' => 'Documento Nacional de Identidad',  'digits' => 8,    'sort_order' => 1],
        ['id' => 2, 'code' => '4', 'name' => 'Carnet de Extranjería', 'description' => 'Carnet de Extranjería',            'digits' => null, 'sort_order' => 2],
        ['id' => 3, 'code' => '6', 'name' => 'RUC',                  'description' => 'Registro Único de Contribuyentes', 'digits' => 11,   'sort_order' => 3],
        ['id' => 4, 'code' => '7', 'name' => 'Pasaporte',            'description' => 'Pasaporte',                        'digits' => null, 'sort_order' => 4],
        ['id' => 5, 'code' => '0', 'name' => 'Sin documento',        'description' => 'Cliente sin documento / varios',   'digits' => null, 'sort_order' => 5],
    ];

    /**
     * Crea los tipos de documento base que falten (por id). Es idempotente.
     *
     * @return int Cantidad de tipos creados
     */
    public static function crearTiposBase(): int
    {
        $existing = self::pluck('id')->all();
        $created  = 0;

        foreach (self::BASE as $type) {
            if (in_array($type['id'], $existing)) {
                continue;
            }

            self::insert($type + ['active' => true]);
            $created++;
        }

        return $created;
    }

    /**
     * Scope para tipos activos, ordenados.
     */
    public function scopeActivos($query)
    {
        return $query->where('active', true)->orderBy('sort_order');
    }

    /**
     * Devuelve el tipo de documento inferido por la longitud del número.
     * 8 dígitos → DNI, 11 dígitos → RUC.
     */
    public static function detectarPorLongitud(string $numero): ?int
    {
        $len = strlen(preg_replace('/\D/', '', $numero));

        return match($len) {
            8  => self::DNI,
            11 => self::RUC,
            default => null,
        };
    }
}
