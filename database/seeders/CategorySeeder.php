<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // company_id 1 = compañía seed (ver migración create_companies_table)
        DB::table('categories')->insert([
            ['company_id' => 1, 'name' => 'Analgésicos y Antipiréticos', 'description' => 'Medicamentos para el dolor y la fiebre',                   'icon' => 'fa-pills',             'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Antibióticos',                'description' => 'Tratamiento de infecciones bacterianas',                   'icon' => 'fa-bacterium',         'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Antiinflamatorios',           'description' => 'Reducción de inflamación y dolor articular',               'icon' => 'fa-fire-flame-simple', 'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Antihipertensivos',           'description' => 'Control de la presión arterial',                          'icon' => 'fa-heart-pulse',       'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Vitaminas y Suplementos',     'description' => 'Vitaminas, minerales y suplementos nutricionales',        'icon' => 'fa-leaf',              'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Antiácidos y Digestivos',     'description' => 'Tratamiento de problemas gastrointestinales',             'icon' => 'fa-shield-halved',     'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Antidiabéticos',              'description' => 'Control de la glucosa en sangre',                         'icon' => 'fa-syringe',           'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Dermatológicos',              'description' => 'Cremas, pomadas y tratamientos para la piel',             'icon' => 'fa-hand-dots',         'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Respiratorios',               'description' => 'Tratamiento de tos, asma y vías respiratorias',           'icon' => 'fa-lungs',             'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Antiparasitarios',            'description' => 'Tratamiento de parásitos intestinales y externos',        'icon' => 'fa-bug',               'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Antihistamínicos',            'description' => 'Tratamiento de alergias y reacciones alérgicas',          'icon' => 'fa-eye',               'status' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => 1, 'name' => 'Oftalmológicos',              'description' => 'Gotas, colirios y medicamentos para los ojos',            'icon' => 'fa-eye-dropper',       'status' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
