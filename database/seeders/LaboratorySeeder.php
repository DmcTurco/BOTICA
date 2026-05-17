<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LaboratorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // company_id 1 = compañía seed (ver migración create_companies_table)
        DB::table('laboratories')->insert([
            ['company_id' => 1, 'name' => 'Bayer',            'description' => 'Multinacional alemana de ciencias de la vida',         'country' => 'Alemania',    'phone' => '+49 214 30-0',      'email' => 'info@bayer.com',          'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Pfizer',           'description' => 'Una de las farmacéuticas más grandes del mundo',       'country' => 'EE.UU.',      'phone' => '+1 212 733-2323',   'email' => 'info@pfizer.com',         'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Roche',            'description' => 'Líder mundial en oncología y diagnóstico',             'country' => 'Suiza',       'phone' => '+41 61 688-1111',   'email' => 'info@roche.com',          'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Abbott',           'description' => 'Tecnología médica y productos farmacéuticos',         'country' => 'EE.UU.',      'phone' => '+1 847 937-6100',   'email' => 'info@abbott.com',         'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Farmex',           'description' => 'Laboratorio peruano de medicamentos genéricos',       'country' => 'Perú',        'phone' => '+51 1 618-5000',    'email' => 'ventas@farmex.com.pe',    'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Medifarma',        'description' => 'Fabricante nacional de genéricos y especialidades',   'country' => 'Perú',        'phone' => '+51 1 511-7000',    'email' => 'info@medifarma.com.pe',   'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Roemmers',         'description' => 'Laboratorio farmacéutico latinoamericano',            'country' => 'Argentina',   'phone' => '+54 11 4339-2000',  'email' => 'info@roemmers.com',       'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Bagó',             'description' => 'Empresa farmacéutica con fuerte presencia regional',  'country' => 'Argentina',   'phone' => '+54 11 4742-3000',  'email' => 'info@bago.com',           'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'GlaxoSmithKline',  'description' => 'Investigación y desarrollo de medicamentos globales', 'country' => 'Reino Unido', 'phone' => '+44 20 8047-5000',  'email' => 'info@gsk.com',            'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Novartis',         'description' => 'Innovación en medicamentos y genéricos (Sandoz)',     'country' => 'Suiza',       'phone' => '+41 61 324-1111',   'email' => 'info@novartis.com',       'status' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
