<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Solo datos del SISTEMA: el resto (productos, laboratorios, clientes...) se registra desde el panel.
     * Las migraciones ya crean el admin, la empresa, la "Sede Principal", un empleado y los roles.
     *
     * Para cargar datos de ejemplo en desarrollo:
     *   php artisan db:seed --class=DemoSeeder
     *
     * Ambos son idempotentes, salvo DemoSeeder (pensado para una base recién migrada).
     */
    public function run(): void
    {
        $this->call([
            DocumentTypeSeeder::class,   // Tipos de documento SUNAT (DNI, RUC...)
            DocumentSeriesSeeder::class, // Correlativos internos + series de las sedes existentes
        ]);
    }
}
