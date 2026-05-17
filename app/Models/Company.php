<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Company extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
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

    /** Sedes de la compañía */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'company_id');
    }

    /** Empleados de la compañía (todas las sedes) */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'company_id');
    }

    /** Categorías del catálogo */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'company_id');
    }

    /** Laboratorios del catálogo */
    public function laboratories(): HasMany
    {
        return $this->hasMany(Laboratory::class, 'company_id');
    }

    /** Productos del catálogo */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'company_id');
    }

    /** Clientes de la compañía */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'company_id');
    }
}
