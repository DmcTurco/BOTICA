<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\BranchStockSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ── Sistema ──────────────────────────────────────────────────────
            DocumentTypeSeeder::class,   // Tipos de doc SUNAT — sin dependencias
            DocumentSeriesSeeder::class, // Correlativos del sistema — sin dependencias

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
        ]);
    }
}
