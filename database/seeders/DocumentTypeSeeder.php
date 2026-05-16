<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Siembra los tipos de documento de identidad según estándar SUNAT.
     */
    public function run(): void
    {
        DB::table('document_types')->insert([
            [
                'id'          => 1,
                'code'        => '1',
                'name'        => 'DNI',
                'description' => 'Documento Nacional de Identidad',
                'digits'      => 8,
                'active'      => true,
                'sort_order'  => 1,
            ],
            [
                'id'          => 2,
                'code'        => '4',
                'name'        => 'Carnet de Extranjería',
                'description' => 'Carnet de Extranjería',
                'digits'      => null,
                'active'      => true,
                'sort_order'  => 2,
            ],
            [
                'id'          => 3,
                'code'        => '6',
                'name'        => 'RUC',
                'description' => 'Registro Único de Contribuyentes',
                'digits'      => 11,
                'active'      => true,
                'sort_order'  => 3,
            ],
            [
                'id'          => 4,
                'code'        => '7',
                'name'        => 'Pasaporte',
                'description' => 'Pasaporte',
                'digits'      => null,
                'active'      => true,
                'sort_order'  => 4,
            ],
            [
                'id'          => 5,
                'code'        => '0',
                'name'        => 'Sin documento',
                'description' => 'Cliente sin documento / varios',
                'digits'      => null,
                'active'      => true,
                'sort_order'  => 5,
            ],
        ]);
    }
}
