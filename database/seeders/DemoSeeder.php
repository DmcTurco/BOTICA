<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Datos de EJEMPLO para desarrollo (4 sedes, catálogo, stock, clientes y SUNAT beta).
     * Pensado para una base recién migrada: BranchSeeder reemplaza las sedes existentes.
     *
     *   php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder
     *
     * No correr en el servidor con datos reales.
     */
    public function run(): void
    {
        $this->call([
            // ── Sistema (por si no se corrió DatabaseSeeder) ──────────────
            DatabaseSeeder::class,

            // ── Sedes ─────────────────────────────────────────────────────
            BranchSeeder::class,         // Requiere: companies (id=1) — reemplaza el placeholder de la migración

            // ── Catálogo (nivel compañía) ─────────────────────────────────
            LaboratorySeeder::class,     // Requiere: companies (id=1)
            UnitSeeder::class,           // Sin dependencias
            CategorySeeder::class,       // Requiere: companies (id=1)
            ProductSeeder::class,        // Requiere: laboratories, units, categories, employees (id=1)
            PresentationSeeder::class,   // Requiere: products, units

            // ── Stock inicial por sede ────────────────────────────────────
            BranchStockSeeder::class,    // Requiere: products, branches (id=1)

            // ── Clientes ──────────────────────────────────────────────────
            ClientSeeder::class,         // Requiere: companies (id=1), document_series (CLIENTE)

            // ── Facturación electrónica (ambiente de pruebas de SUNAT) ─────
            CompanySunatSettingSeeder::class, // Requiere: companies (id=1)
        ]);
    }
}
