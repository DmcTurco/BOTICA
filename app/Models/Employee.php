<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ── Constantes de privilegios (solo aplican a role_id = 3) ──
    const PRIV_VER_VENTAS      = 'ver_ventas';
    const PRIV_VER_HISTORIAL   = 'ver_historial';
    const PRIV_ABRIR_CAJA      = 'abrir_caja';
    const PRIV_CERRAR_CAJA     = 'cerrar_caja';
    const PRIV_EDITAR_APERTURA = 'editar_apertura';

    /** Lista completa de privilegios disponibles con sus etiquetas */
    const PRIVILEGES_LIST = [
        self::PRIV_VER_VENTAS      => 'Ver Punto de Venta',
        self::PRIV_VER_HISTORIAL   => 'Ver Historial de Ventas',
        self::PRIV_ABRIR_CAJA      => 'Abrir Caja',
        self::PRIV_CERRAR_CAJA     => 'Cerrar Caja',
        self::PRIV_EDITAR_APERTURA => 'Editar Apertura de Caja',
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'role_id',
        'name',
        'email',
        'password',
        'privileges',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'privileges'        => 'array',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    /** Compañía a la que pertenece */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Sede principal del empleado */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** Rol asignado */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** Historial de sedes y roles del empleado */
    public function histories(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class, 'employee_id');
    }

    /** Registro activo en el historial (sin ended_at) */
    public function currentHistory()
    {
        return $this->hasOne(EmployeeHistory::class, 'employee_id')->whereNull('ended_at');
    }

    /** Cajas que ha abierto este empleado */
    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class, 'employee_id');
    }

    /** Órdenes/ventas registradas por este empleado */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'employee_id');
    }

    /** Compras registradas por este empleado */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'employee_id');
    }

    /** Empleados a los que autorizó cambios de sede/rol */
    public function authorizedHistories(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class, 'authorized_by');
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** ¿Es administrador de compañía? */
    public function isCompanyAdmin(): bool
    {
        return $this->role_id === Role::COMPANY_ADMIN;
    }

    /** ¿Es administrador de sede? */
    public function isBranchAdmin(): bool
    {
        return $this->role_id === Role::BRANCH_ADMIN;
    }

    /** ¿Es empleado regular? */
    public function isEmployee(): bool
    {
        return $this->role_id === Role::EMPLOYEE;
    }

    /**
     * Verifica si el empleado tiene un privilegio específico.
     * Los branch_admin (role_id=2) siempre tienen acceso total.
     * Los empleados regulares (role_id=3) solo tienen acceso a los privilegios asignados.
     */
    public function hasPrivilege(string $privilege): bool
    {
        // branch_admin tiene acceso a todo sin restricción
        if ($this->isBranchAdmin()) {
            return true;
        }

        return in_array($privilege, $this->privileges ?? [], true);
    }

    /**
     * Verifica si el empleado tiene al menos un privilegio asignado.
     * Los branch_admin siempre retornan true.
     * Empleados sin ningún privilegio solo pueden ver el dashboard.
     */
    public function hasAnyPrivilege(): bool
    {
        if ($this->isBranchAdmin()) {
            return true;
        }

        return !empty($this->privileges);
    }
}
