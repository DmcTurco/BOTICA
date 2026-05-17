<?php

namespace Database\Seeders;

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
                'address'    => 'Av. Próceres de la Independencia 1200, SJL',
                'phone'      => '+51 1 376-9900',
                'email'      => 'sede.este@botica.com',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
