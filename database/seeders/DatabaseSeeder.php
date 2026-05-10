<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LaboratorySeeder::class,   // Sin dependencias
            UnitSeeder::class,         // Sin dependencias
            CategorySeeder::class,     // Sin dependencias
            ProductSeeder::class,      // Requiere: laboratoies, units, categories
            PresentationSeeder::class, // Requiere: products, units
        ]);
    }
}
