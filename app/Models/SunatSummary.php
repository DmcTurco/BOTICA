<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Resumen diario de boletas enviado a SUNAT (identificador RC-AAAAMMDD-n).
 */
class SunatSummary extends Model
{
    protected $table = 'sunat_summaries';

    const STATUS_PENDING    = 'pending';     // creado, aún no enviado
    const STATUS_PROCESSING = 'processing';  // SUNAT lo recibió (ticket) y aún lo procesa
    const STATUS_ACCEPTED   = 'accepted';
    const STATUS_REJECTED   = 'rejected';
    const STATUS_ERROR      = 'error';       // falló la conexión o el envío

    protected $fillable = [
        'company_id',
        'reference_date',
        'generation_date',
        'correlative',
        'identifier',
        'documents_count',
        'ticket',
        'status',
        'code',
        'message',
        'environment',
        'xml_path',
        'cdr_path',
        'sent_at',
    ];

    protected $casts = [
        'reference_date'  => 'date',
        'generation_date' => 'date',
        'sent_at'         => 'datetime',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'sunat_summary_id');
    }
}
