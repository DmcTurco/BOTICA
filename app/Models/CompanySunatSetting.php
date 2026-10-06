<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySunatSetting extends Model
{
    protected $table = 'company_sunat_settings';

    const ENV_BETA       = 'beta';
    const ENV_PRODUCTION = 'production';

    protected $fillable = [
        'company_id',
        'legal_name',
        'trade_name',
        'fiscal_address',
        'ubigeo',
        'department',
        'province',
        'district',
        'environment',
        'sol_user',
        'sol_password',
        'certificate_path',
        'certificate_password',
        'certificate_subject',
        'certificate_expires_at',
        'enabled',
    ];

    /** Nunca se serializan las credenciales ni la ruta del certificado */
    protected $hidden = [
        'sol_password',
        'certificate_password',
        'certificate_path',
    ];

    protected $casts = [
        'sol_password'           => 'encrypted',
        'certificate_password'   => 'encrypted',
        'certificate_expires_at' => 'date',
        'enabled'                => 'boolean',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** ¿Tiene certificado digital cargado? */
    public function hasCertificate(): bool
    {
        return !empty($this->certificate_path);
    }

    /** ¿Tiene usuario y clave SOL? */
    public function hasSolCredentials(): bool
    {
        return !empty($this->sol_user) && !empty($this->sol_password);
    }

    /** ¿El certificado ya venció? */
    public function certificateExpired(): bool
    {
        return $this->certificate_expires_at !== null && $this->certificate_expires_at->isPast();
    }

    /** ¿El certificado vence en los próximos $days días? */
    public function certificateExpiresSoon(int $days = 30): bool
    {
        return $this->certificate_expires_at !== null
            && !$this->certificateExpired()
            && $this->certificate_expires_at->lte(now()->addDays($days));
    }

    /** Campos fiscales que faltan completar (para mostrar el estado) */
    public function missingFields(): array
    {
        $missing = [];

        if (empty($this->company?->ruc))   $missing[] = 'RUC';
        if (empty($this->legal_name))      $missing[] = 'Razón social';
        if (empty($this->fiscal_address))  $missing[] = 'Dirección fiscal';
        if (empty($this->ubigeo))          $missing[] = 'Ubigeo';
        if (!$this->hasSolCredentials())   $missing[] = 'Usuario y clave SOL';
        if (!$this->hasCertificate())      $missing[] = 'Certificado digital';

        return $missing;
    }

    /** ¿Está todo completo para poder emitir? */
    public function isReady(): bool
    {
        return empty($this->missingFields()) && !$this->certificateExpired();
    }
}
