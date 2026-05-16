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
