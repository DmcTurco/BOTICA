<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota de crédito electrónica: anula por completo una boleta o factura aceptada por SUNAT.
 * El detalle y los importes son los de la venta original (order).
 */
class CreditNote extends Model
{
    protected $table = 'credit_notes';

    // Motivos del catálogo SUNAT 09 que se ofrecen (todos anulan la venta completa)
    const REASON_CANCELLATION  = '01'; // Anulación de la operación
    const REASON_WRONG_RUC     = '02'; // Anulación por error en el RUC
    const REASON_FULL_RETURN   = '06'; // Devolución total

    const REASONS = [
        self::REASON_CANCELLATION => 'Anulación de la operación',
        self::REASON_WRONG_RUC    => 'Anulación por error en el RUC',
        self::REASON_FULL_RETURN  => 'Devolución total',
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'order_id',
        'employee_id',
        'voucher_number',
        'reason_code',
        'reason_text',
        'taxable_amount',
        'exonerated_amount',
        'unaffected_amount',
        'subtotal',
        'igv',
        'total',
        'sunat_status',
        'sunat_code',
        'sunat_message',
        'sunat_hash',
        'sunat_xml_path',
        'sunat_cdr_path',
        'sunat_environment',
        'sunat_attempts',
        'sunat_sent_at',
    ];

    protected $casts = [
        'taxable_amount'    => 'decimal:2',
        'exonerated_amount' => 'decimal:2',
        'unaffected_amount' => 'decimal:2',
        'subtotal'          => 'decimal:2',
        'igv'               => 'decimal:2',
        'total'             => 'decimal:2',
        'sunat_attempts'    => 'integer',
        'sunat_sent_at'     => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Venta que se anula */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // ── Helpers SUNAT (mismos estados que en Order) ─────────────

    /** ¿Se puede reenviar a SUNAT? (pendiente o con error de envío) */
    public function canResendToSunat(): bool
    {
        return in_array($this->sunat_status, [Order::SUNAT_PENDING, Order::SUNAT_ERROR], true);
    }

    /** Etiqueta legible del estado SUNAT */
    public function sunatLabel(): string
    {
        return Order::SUNAT_LABELS[$this->sunat_status] ?? 'Desconocido';
    }
}
