<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DocumentTypeSeeder::class,   // Sin dependencias — debe ir primero
            DocumentSeriesSeeder::class, // Correlativos de todos los documentos del sistema
            LaboratorySeeder::class,    // Sin dependencias
            UnitSeeder::class,          // Sin dependencias
            CategorySeeder::class,      // Sin dependencias
            ProductSeeder::class,       // Requiere: laboratories, units, categories
            PresentationSeeder::class,  // Requiere: products, units
        ]);
    }
}
