<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Services\CompanyStarterCatalog;
use Illuminate\Database\Seeder;

class StarterCatalogSeeder extends Seeder
{
    /**
     * Catálogo base (unidades, categorías y laboratorios) para la compañía seed (id = 1),
     * el mismo que recibe toda compañía creada desde el panel del admin.
     */
    public function run(): void
    {
        CompanyStarterCatalog::seed(Company::findOrFail(1));
    }
}
