<?php

namespace Database\Seeders;

use App\Models\DocumentSeries;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Resolvemos IDs por nombre para no hardcodear
        $cat  = fn(string $n) => DB::table('categories')->where('name', $n)->value('id');
        $lab  = fn(string $n) => DB::table('laboratories')->where('name', $n)->value('id');
        $unit = fn(string $n) => DB::table('units')->where('name', $n)->value('id');

        // company_id 1 = compañía seed · employee_id 1 = empleado seed
        $products = [
            // Analgésicos
            [
                'name'              => 'Paracetamol 500mg',
                'description'       => 'Analgésico y antipirético de uso común',
                'company_id'        => 1,
                'category_id'       => $cat('Analgésicos y Antipiréticos'),
                'laboratory_id'     => $lab('Bayer'),
                'active_ingredient' => 'Paracetamol',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.20, 'unit_sale_price'        => 0.50,
                'package_purchase_price' => 1.50, 'package_sale_price'     => 4.00, 'units_per_package' => 10,
                'taxed_product' => false, 'requires_recipe' => false,
                'location' => 'A-01', 'status' => 1, 'employee_id' => 1,
            ],
            [
                'name'              => 'Ibuprofeno 400mg',
                'description'       => 'Antiinflamatorio no esteroideo (AINE)',
                'company_id'        => 1,
                'category_id'       => $cat('Antiinflamatorios'),
                'laboratory_id'     => $lab('Pfizer'),
                'active_ingredient' => 'Ibuprofeno',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.30, 'unit_sale_price'        => 0.70,
                'package_purchase_price' => 2.50, 'package_sale_price'     => 6.00, 'units_per_package' => 10,
                'taxed_product' => false, 'requires_recipe' => false,
                'location' => 'A-02', 'status' => 1, 'employee_id' => 1,
            ],
            [
                'name'              => 'Naproxeno 550mg',
                'description'       => 'Antiinflamatorio para dolor moderado a intenso',
                'company_id'        => 1,
                'category_id'       => $cat('Antiinflamatorios'),
                'laboratory_id'     => $lab('Novartis'),
                'active_ingredient' => 'Naproxeno sódico',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.50, 'unit_sale_price'        => 1.20,
                'package_purchase_price' => 4.00, 'package_sale_price'     => 10.00, 'units_per_package' => 10,
                'taxed_product' => false, 'requires_recipe' => false,
                'location' => 'A-03', 'status' => 1, 'employee_id' => 1,
            ],
            // Antibióticos
            [
                'name'              => 'Amoxicilina 500mg',
                'description'       => 'Antibiótico de amplio espectro (penicilínico)',
                'company_id'        => 1,
                'category_id'       => $cat('Antibióticos'),
                'laboratory_id'     => $lab('Farmex'),
                'active_ingredient' => 'Amoxicilina',
                'unit_id'           => $unit('Cápsula'),
                'purchase_price'         => 0.40, 'unit_sale_price'        => 0.90,
                'package_purchase_price' => 3.50, 'package_sale_price'     => 7.50, 'units_per_package' => 10,
                'taxed_product' => false, 'requires_recipe' => true,
                'location' => 'B-01', 'status' => 1, 'employee_id' => 1,
            ],
            [
                'name'              => 'Azitromicina 500mg',
                'description'       => 'Antibiótico macrólido de amplio espectro',
                'company_id'        => 1,
                'category_id'       => $cat('Antibióticos'),
                'laboratory_id'     => $lab('Pfizer'),
                'active_ingredient' => 'Azitromicina',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 1.20, 'unit_sale_price'        => 2.80,
                'package_purchase_price' => 5.50, 'package_sale_price'     => 12.00, 'units_per_package' => 6,
                'taxed_product' => false, 'requires_recipe' => true,
                'location' => 'B-02', 'status' => 1, 'employee_id' => 1,
            ],
            // Antihipertensivos
            [
                'name'              => 'Amlodipino 5mg',
                'description'       => 'Calcioantagonista para hipertensión arterial',
                'company_id'        => 1,
                'category_id'       => $cat('Antihipertensivos'),
                'laboratory_id'     => $lab('Roemmers'),
                'active_ingredient' => 'Amlodipino besilato',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.25, 'unit_sale_price'        => 0.60,
                'package_purchase_price' => 2.00, 'package_sale_price'     => 5.50, 'units_per_package' => 10,
                'taxed_product' => false, 'requires_recipe' => true,
                'location' => 'C-01', 'status' => 1, 'employee_id' => 1,
            ],
            [
                'name'              => 'Enalapril 10mg',
                'description'       => 'Inhibidor de la ECA para hipertensión e insuficiencia cardíaca',
                'company_id'        => 1,
                'category_id'       => $cat('Antihipertensivos'),
                'laboratory_id'     => $lab('Medifarma'),
                'active_ingredient' => 'Enalapril maleato',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.15, 'unit_sale_price'        => 0.40,
                'package_purchase_price' => 1.20, 'package_sale_price'     => 3.50, 'units_per_package' => 10,
                'taxed_product' => false, 'requires_recipe' => true,
                'location' => 'C-02', 'status' => 1, 'employee_id' => 1,
            ],
            // Digestivos
            [
                'name'              => 'Omeprazol 20mg',
                'description'       => 'Inhibidor de la bomba de protones (IBP)',
                'company_id'        => 1,
                'category_id'       => $cat('Antiácidos y Digestivos'),
                'laboratory_id'     => $lab('Bagó'),
                'active_ingredient' => 'Omeprazol',
                'unit_id'           => $unit('Cápsula'),
                'purchase_price'         => 0.30, 'unit_sale_price'        => 0.70,
                'package_purchase_price' => 2.50, 'package_sale_price'     => 6.00, 'units_per_package' => 10,
                'taxed_product' => false, 'requires_recipe' => false,
                'location' => 'D-01', 'status' => 1, 'employee_id' => 1,
            ],
            // Antidiabéticos
            [
                'name'              => 'Metformina 500mg',
                'description'       => 'Antidiabético oral de primera línea (biguanida)',
                'company_id'        => 1,
                'category_id'       => $cat('Antidiabéticos'),
                'laboratory_id'     => $lab('Abbott'),
                'active_ingredient' => 'Metformina clorhidrato',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.20, 'unit_sale_price'        => 0.50,
                'package_purchase_price' => 1.80, 'package_sale_price'     => 4.50, 'units_per_package' => 10,
                'taxed_product' => false, 'requires_recipe' => true,
                'location' => 'E-01', 'status' => 1, 'employee_id' => 1,
            ],
            // Vitaminas
            [
                'name'              => 'Vitamina C 500mg',
                'description'       => 'Ácido ascórbico, antioxidante y estimulante inmune',
                'company_id'        => 1,
                'category_id'       => $cat('Vitaminas y Suplementos'),
                'laboratory_id'     => $lab('GlaxoSmithKline'),
                'active_ingredient' => 'Ácido ascórbico',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.15, 'unit_sale_price'        => 0.35,
                'package_purchase_price' => 1.20, 'package_sale_price'     => 3.00, 'units_per_package' => 10,
                'taxed_product' => false, 'requires_recipe' => false,
                'location' => 'F-01', 'status' => 1, 'employee_id' => 1,
            ],
            [
                'name'              => 'Vitamina B12 1000mcg',
                'description'       => 'Cianocobalamina para anemia y sistema nervioso',
                'company_id'        => 1,
                'category_id'       => $cat('Vitaminas y Suplementos'),
                'laboratory_id'     => $lab('Roche'),
                'active_ingredient' => 'Cianocobalamina',
                'unit_id'           => $unit('Ampolla'),
                'purchase_price'         => 1.50, 'unit_sale_price'        => 3.50,
                'package_purchase_price' => null, 'package_sale_price'     => null, 'units_per_package' => null,
                'taxed_product' => false, 'requires_recipe' => false,
                'location' => 'F-02', 'status' => 1, 'employee_id' => 1,
            ],
            // Dermatológicos
            [
                'name'              => 'Diclofenaco Gel 1% 50g',
                'description'       => 'Antiinflamatorio tópico para dolor muscular y articular',
                'company_id'        => 1,
                'category_id'       => $cat('Dermatológicos'),
                'laboratory_id'     => $lab('Bayer'),
                'active_ingredient' => 'Diclofenaco dietilamonio',
                'unit_id'           => $unit('Tubo'),
                'purchase_price'         => 8.00, 'unit_sale_price'        => 15.00,
                'package_purchase_price' => null, 'package_sale_price'     => null, 'units_per_package' => null,
                'taxed_product' => true, 'requires_recipe' => false,
                'location' => 'G-01', 'status' => 1, 'employee_id' => 1,
            ],
            // Antihistamínicos
            [
                'name'              => 'Loratadina 10mg',
                'description'       => 'Antihistamínico de segunda generación, no sedante',
                'company_id'        => 1,
                'category_id'       => $cat('Antihistamínicos'),
                'laboratory_id'     => $lab('Medifarma'),
                'active_ingredient' => 'Loratadina',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.25, 'unit_sale_price'        => 0.60,
                'package_purchase_price' => 2.00, 'package_sale_price'     => 5.50, 'units_per_package' => 10,
                'taxed_product' => false, 'requires_recipe' => false,
                'location' => 'H-01', 'status' => 1, 'employee_id' => 1,
            ],
            // Antiparasitarios
            [
                'name'              => 'Albendazol 400mg',
                'description'       => 'Antiparasitario de amplio espectro',
                'company_id'        => 1,
                'category_id'       => $cat('Antiparasitarios'),
                'laboratory_id'     => $lab('Farmex'),
                'active_ingredient' => 'Albendazol',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.60, 'unit_sale_price'        => 1.50,
                'package_purchase_price' => null, 'package_sale_price'     => null, 'units_per_package' => null,
                'taxed_product' => false, 'requires_recipe' => false,
                'location' => 'I-01', 'status' => 1, 'employee_id' => 1,
            ],
            // Oftalmológicos
            [
                'name'              => 'Cloranfenicol Colirio 0.5%',
                'description'       => 'Antibiótico oftálmico para conjuntivitis bacteriana',
                'company_id'        => 1,
                'category_id'       => $cat('Oftalmológicos'),
                'laboratory_id'     => $lab('Roemmers'),
                'active_ingredient' => 'Cloranfenicol',
                'unit_id'           => $unit('Frasco'),
                'purchase_price'         => 3.50, 'unit_sale_price'        => 7.00,
                'package_purchase_price' => null, 'package_sale_price'     => null, 'units_per_package' => null,
                'taxed_product' => false, 'requires_recipe' => true,
                'location' => 'J-01', 'status' => 1, 'employee_id' => 1,
            ],
        ];

        // Genera el código automático desde document_series para cada producto
        DB::transaction(function () use ($products, $now) {
            foreach ($products as $product) {
                DB::table('products')->insert(array_merge($product, [
                    'code'       => DocumentSeries::siguiente(DocumentSeries::PRODUCTO),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        });
    }
}
