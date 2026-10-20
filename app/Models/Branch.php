<?php

namespace App\Models;

use App\Traits\HasConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasConfig;
    protected $table = 'branches';

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'phone',
        'email',
        'sunat_establishment_code',
        'uses_fiscal_establishment',
        'status',
    ];

    protected $casts = [
        'status'                    => 'integer',
        'uses_fiscal_establishment' => 'integer',
    ];

    /** Código del domicilio fiscal en la ficha RUC: siempre 0000 */
    const FISCAL_ESTABLISHMENT_CODE = '0000';

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía a la que pertenece la sede */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Series de comprobantes de la sede (boleta, factura, nota de venta) */
    public function documentSeries(): HasMany
    {
        return $this->hasMany(DocumentSeries::class, 'branch_id');
    }

    /** Empleados cuya sede principal es esta */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'branch_id');
    }

    /** Cajas registradoras de esta sede */
    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class, 'branch_id');
    }

    /** Órdenes/ventas realizadas en esta sede */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'branch_id');
    }

    /** Compras realizadas en esta sede */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'branch_id');
    }

    /** Movimientos de stock de esta sede */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'branch_id');
    }

    /** Stock de productos en esta sede */
    public function branchStocks(): HasMany
    {
        return $this->hasMany(BranchStock::class, 'branch_id');
    }

    // ── Scopes ──────────────────────────────────────────────────

    /** Solo sedes activas */
    public function scopeActivas($query)
    {
        return $query->where('status', 1);
    }

    // ── Código de establecimiento SUNAT ─────────────────────────
    // Cada comprobante declara el local que lo emite con este código de la ficha RUC
    // (0000 domicilio fiscal, 0001, 0002... anexos). No se puede repetir dentro de la empresa,
    // salvo las sedes que aún no son anexo en SUNAT: emiten con el código del domicilio fiscal.

    /**
     * Código de establecimiento que va en los comprobantes de la sede.
     *
     * @return string
     */
    public function sunatEstablishmentCode(): string
    {
        return $this->uses_fiscal_establishment === 1
            ? self::FISCAL_ESTABLISHMENT_CODE
            : ($this->sunat_establishment_code ?: self::FISCAL_ESTABLISHMENT_CODE);
    }

    /**
     * Primer código libre de la empresa, para proponerlo al crear una sede.
     *
     * @param  int  $companyId
     * @return string
     */
    public static function nextEstablishmentCode(int $companyId): string
    {
        $used = self::where('company_id', $companyId)->where('uses_fiscal_establishment', 0)
            ->pluck('sunat_establishment_code')->flip();

        for ($n = 0; $n <= 9999; $n++) {
            $code = str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            if (!isset($used[$code])) {
                return $code;
            }
        }

        return '0000';
    }

    /**
     * Códigos usados por más de una sede de la empresa: [código => [nombres de las sedes]].
     * Sedes creadas antes de validar la unicidad pueden compartir el 0000. No cuentan las que
     * usan a propósito el código del domicilio fiscal (aún no son anexo en SUNAT).
     *
     * @param  int  $companyId
     * @return array<string, array<int, string>>
     */
    public static function duplicateEstablishmentCodes(int $companyId): array
    {
        return self::where('company_id', $companyId)
            ->where('uses_fiscal_establishment', 0)
            ->orderBy('name')
            ->get(['name', 'sunat_establishment_code'])
            ->groupBy('sunat_establishment_code')
            ->filter(fn ($branches) => $branches->count() > 1)
            ->map(fn ($branches) => $branches->pluck('name')->all())
            ->all();
    }
}
