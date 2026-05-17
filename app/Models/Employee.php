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

    protected $fillable = [
        'company_id',
        'branch_id',
        'role_id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
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
}
