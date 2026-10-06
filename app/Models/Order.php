<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    // ── Estado del envío a SUNAT ────────────────────────────────
    const SUNAT_NOT_APPLICABLE = 'not_applicable'; // nota de venta o empresa sin facturación electrónica
    const SUNAT_PENDING        = 'pending';        // por enviar (o reintentar)
    const SUNAT_ACCEPTED       = 'accepted';       // SUNAT aceptó el comprobante (CDR recibido)
    const SUNAT_REJECTED       = 'rejected';       // SUNAT lo rechazó: hay que corregir y emitir de nuevo
    const SUNAT_ERROR          = 'error';          // falló la conexión o el envío: se puede reintentar

    const SUNAT_LABELS = [
        self::SUNAT_NOT_APPLICABLE => 'No aplica',
        self::SUNAT_PENDING        => 'Pendiente',
        self::SUNAT_ACCEPTED       => 'Aceptado',
        self::SUNAT_REJECTED       => 'Rechazado',
        self::SUNAT_ERROR          => 'Error de envío',
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'cash_register_id',
        'employee_id',
        'client_id',
        'customer_name',
        'document_type_id',
        'customer_document',
        'voucher_type',
        'voucher_number',
        'payment_type',
        'operation_number',
        'subtotal',
        'taxable_amount',
        'exonerated_amount',
        'unaffected_amount',
        'igv',
        'total',
        'status',
        'sunat_status',
        'sunat_code',
        'sunat_message',
        'sunat_hash',
        'sunat_xml_path',
        'sunat_cdr_path',
        'sunat_environment',
        'sunat_attempts',
        'sunat_sent_at',
        'sunat_summary_id',
    ];

    protected $casts = [
        'sunat_attempts' => 'integer',
        'sunat_sent_at'  => 'datetime',
        'subtotal'          => 'decimal:2',
        'taxable_amount'    => 'decimal:2',
        'exonerated_amount' => 'decimal:2',
        'unaffected_amount' => 'decimal:2',
        'igv'               => 'decimal:2',
        'total'             => 'decimal:2',
    ];

    /** Desde este total (exclusivo) la boleta exige identificar al cliente (SUNAT) */
    const BOLETA_ID_THRESHOLD = 700;

    // ── Helpers SUNAT ───────────────────────────────────────────

    /** ¿Es un comprobante que se informa a SUNAT? (boleta o factura; la nota de venta no) */
    public function isSunatVoucher(): bool
    {
        return in_array((int) $this->voucher_type, [1, 2], true);
    }

    /** ¿Es una boleta de venta? (la factura va siempre individual) */
    public function isBoleta(): bool
    {
        return (int) $this->voucher_type === 1;
    }

    /** ¿Se puede reenviar a SUNAT? (pendiente o con error de envío) */
    public function canResendToSunat(): bool
    {
        return in_array($this->sunat_status, [self::SUNAT_PENDING, self::SUNAT_ERROR], true);
    }

    /** ¿Se puede anular con una nota de crédito? (boleta o factura aceptada, activa y sin nota) */
    public function canIssueCreditNote(): bool
    {
        return (bool) $this->status
            && $this->isSunatVoucher()
            && $this->sunat_status === self::SUNAT_ACCEPTED
            && !$this->creditNote;
    }

    /** Etiqueta legible del estado SUNAT */
    public function sunatLabel(): string
    {
        return self::SUNAT_LABELS[$this->sunat_status] ?? 'Desconocido';
    }

    // ── Relaciones ──────────────────────────────────────────────

    /** Nota de crédito que anula esta venta (si existe) */
    public function creditNote(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CreditNote::class, 'order_id');
    }

    /** Compañía a la que pertenece */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Sede donde se realizó la venta */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** Caja en la que se registró */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    /** Empleado que realizó la venta */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Cliente registrado (null = venta anónima) */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /** Tipo de documento del cliente */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    /** Ítems de la orden */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}
