<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'code',
        'name',
        'document_type_id',
        'document_number',
        'phone',
        'email',
        'address',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    // Tipo de documento de identidad del cliente
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    // Scope para clientes activos
    public function scopeActivos($query)
    {
        return $query->where('status', 1);
    }
}
