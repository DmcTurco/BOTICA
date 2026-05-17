<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'description',
    ];

    // ── Constantes ──────────────────────────────────────────────

    /** Administrador de la compañía — acceso total */
    const COMPANY_ADMIN = 1;

    /** Administrador de sede — gestiona su sede y catálogo */
    const BRANCH_ADMIN = 2;

    /** Empleado de sede — solo operaciones del día a día */
    const EMPLOYEE = 3;

    // ── Relaciones ──────────────────────────────────────────────

    /** Empleados que tienen este rol */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'role_id');
    }

    /** Historial de empleados con este rol */
    public function employeeHistories(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class, 'role_id');
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** Verifica si el rol es administrador de compañía */
    public function isCompanyAdmin(): bool
    {
        return $this->id === self::COMPANY_ADMIN;
    }

    /** Verifica si el rol es administrador de sede */
    public function isBranchAdmin(): bool
    {
        return $this->id === self::BRANCH_ADMIN;
    }

    /** Verifica si el rol es empleado regular */
    public function isEmployee(): bool
    {
        return $this->id === self::EMPLOYEE;
    }
}
