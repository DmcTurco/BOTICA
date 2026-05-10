<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('units')->insert([
            ['name' => 'Unidad',    'abbreviation' => 'UND',  'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tableta',   'abbreviation' => 'TAB',  'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Cápsula',   'abbreviation' => 'CAP',  'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Blíster',   'abbreviation' => 'BLI',  'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Caja',      'abbreviation' => 'CJA',  'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Frasco',    'abbreviation' => 'FRC',  'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ampolla',   'abbreviation' => 'AMP',  'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sobre',     'abbreviation' => 'SOB',  'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tira',      'abbreviation' => 'TIR',  'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tubo',      'abbreviation' => 'TUB',  'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mililitro', 'abbreviation' => 'ML',   'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gramo',     'abbreviation' => 'GR',   'status' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
