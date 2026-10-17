<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DocumentSeries;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    /**
     * Siembra las sedes de la compañía seed (company_id = 1).
     * Reemplaza el registro mínimo que inserta la migración con datos realistas.
     * Se mantiene id=1 explícito para que el employee seed siga siendo válido.
     */
    public function run(): void
    {
        $now = now();

        // Limpiamos el placeholder de la migración y reiniciamos la secuencia (PostgreSQL)
        DB::statement('TRUNCATE TABLE branches RESTART IDENTITY CASCADE');

        DB::table('branches')->insert([
            [
                'company_id' => 1,
                'name'       => 'Sede Principal — Centro',
                'sunat_establishment_code' => '0000', // domicilio fiscal
                'address'    => 'Av. Abancay 123, Cercado de Lima',
                'phone'      => '+51 1 428-0000',
                'email'      => 'sede.centro@botica.com',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_id' => 1,
                'name'       => 'Sede Norte — Los Olivos',
                'sunat_establishment_code' => '0001', // establecimiento anexo
                'address'    => 'Av. Universitaria 4500, Los Olivos',
                'phone'      => '+51 1 533-1100',
                'email'      => 'sede.norte@botica.com',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_id' => 1,
                'name'       => 'Sede Sur — Miraflores',
                'sunat_establishment_code' => '0002', // establecimiento anexo
                'address'    => 'Av. Larco 740, Miraflores',
                'phone'      => '+51 1 241-5500',
                'email'      => 'sede.sur@botica.com',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_id' => 1,
                'name'       => 'Sede Este — San Juan de Lurigancho',
                'sunat_establishment_code' => '0003', // establecimiento anexo
                'address'    => 'Av. Próceres de la Independencia 1200, SJL',
                'phone'      => '+51 1 376-9900',
                'email'      => 'sede.este@botica.com',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Series de comprobantes de cada sede (B001, F001, NV01 / B002, F002, NV02...)
        foreach (Branch::orderBy('id')->get() as $branch) {
            DocumentSeries::crearSeriesParaSede($branch);
        }
    }
}
