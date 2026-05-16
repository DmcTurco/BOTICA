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

        $products = [
            // Analgésicos
            [
                'came'              => 'Paracetamol 500mg',
                'description'       => 'Analgésico y antipirético de uso común',
                'category_id'       => $cat('Analgésicos y Antipiréticos'),
                'laboratory_id'     => $lab('Bayer'),
                'active_ingredient' => 'Paracetamol',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.20,  'unit_sale_price'        => 0.50,
                'package_purchase_price' => 1.50,  'package_sale_price'     => 4.00, 'units_per_package' => 10,
                'stock_actual' => 500, 'stock_minimum' => 50, 'stock_maximum' => 1000,
                'taxed_product' => false, 'requires_recipe' => false,
                'expiration_date' => '2027-06-30', 'location' => 'A-01', 'status' => 1,
            ],
            [
                'came'              => 'Ibuprofeno 400mg',
                'description'       => 'Antiinflamatorio no esteroideo (AINE)',
                'category_id'       => $cat('Antiinflamatorios'),
                'laboratory_id'     => $lab('Pfizer'),
                'active_ingredient' => 'Ibuprofeno',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.30,  'unit_sale_price'        => 0.70,
                'package_purchase_price' => 2.50,  'package_sale_price'     => 6.00, 'units_per_package' => 10,
                'stock_actual' => 400, 'stock_minimum' => 40, 'stock_maximum' => 800,
                'taxed_product' => false, 'requires_recipe' => false,
                'expiration_date' => '2027-03-31', 'location' => 'A-02', 'status' => 1,
            ],
            [
                'came'              => 'Naproxeno 550mg',
                'description'       => 'Antiinflamatorio para dolor moderado a intenso',
                'category_id'       => $cat('Antiinflamatorios'),
                'laboratory_id'     => $lab('Novartis'),
                'active_ingredient' => 'Naproxeno sódico',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.50,  'unit_sale_price'        => 1.20,
                'package_purchase_price' => 4.00,  'package_sale_price'     => 10.00, 'units_per_package' => 10,
                'stock_actual' => 300, 'stock_minimum' => 30, 'stock_maximum' => 600,
                'taxed_product' => false, 'requires_recipe' => false,
                'expiration_date' => '2026-12-31', 'location' => 'A-03', 'status' => 1,
            ],
            // Antibióticos
            [
                'came'              => 'Amoxicilina 500mg',
                'description'       => 'Antibiótico de amplio espectro (penicilínico)',
                'category_id'       => $cat('Antibióticos'),
                'laboratory_id'     => $lab('Farmex'),
                'active_ingredient' => 'Amoxicilina',
                'unit_id'           => $unit('Cápsula'),
                'purchase_price'         => 0.40,  'unit_sale_price'        => 0.90,
                'package_purchase_price' => 3.50,  'package_sale_price'     => 7.50, 'units_per_package' => 10,
                'stock_actual' => 350, 'stock_minimum' => 50, 'stock_maximum' => 700,
                'taxed_product' => false, 'requires_recipe' => true,
                'expiration_date' => '2026-09-30', 'location' => 'B-01', 'status' => 1,
            ],
            [
                'came'              => 'Azitromicina 500mg',
                'description'       => 'Antibiótico macrólido de amplio espectro',
                'category_id'       => $cat('Antibióticos'),
                'laboratory_id'     => $lab('Pfizer'),
                'active_ingredient' => 'Azitromicina',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 1.20,  'unit_sale_price'        => 2.80,
                'package_purchase_price' => 5.50,  'package_sale_price'     => 12.00, 'units_per_package' => 6,
                'stock_actual' => 200, 'stock_minimum' => 30, 'stock_maximum' => 400,
                'taxed_product' => false, 'requires_recipe' => true,
                'expiration_date' => '2027-01-31', 'location' => 'B-02', 'status' => 1,
            ],
            // Antihipertensivos
            [
                'came'              => 'Amlodipino 5mg',
                'description'       => 'Calcioantagonista para hipertensión arterial',
                'category_id'       => $cat('Antihipertensivos'),
                'laboratory_id'     => $lab('Roemmers'),
                'active_ingredient' => 'Amlodipino besilato',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.25,  'unit_sale_price'        => 0.60,
                'package_purchase_price' => 2.00,  'package_sale_price'     => 5.50, 'units_per_package' => 10,
                'stock_actual' => 600, 'stock_minimum' => 60, 'stock_maximum' => 1200,
                'taxed_product' => false, 'requires_recipe' => true,
                'expiration_date' => '2027-08-31', 'location' => 'C-01', 'status' => 1,
            ],
            [
                'came'              => 'Enalapril 10mg',
                'description'       => 'Inhibidor de la ECA para hipertensión e insuficiencia cardíaca',
                'category_id'       => $cat('Antihipertensivos'),
                'laboratory_id'     => $lab('Medifarma'),
                'active_ingredient' => 'Enalapril maleato',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.15,  'unit_sale_price'        => 0.40,
                'package_purchase_price' => 1.20,  'package_sale_price'     => 3.50, 'units_per_package' => 10,
                'stock_actual' => 550, 'stock_minimum' => 60, 'stock_maximum' => 1100,
                'taxed_product' => false, 'requires_recipe' => true,
                'expiration_date' => '2027-04-30', 'location' => 'C-02', 'status' => 1,
            ],
            // Digestivos
            [
                'came'              => 'Omeprazol 20mg',
                'description'       => 'Inhibidor de la bomba de protones (IBP)',
                'category_id'       => $cat('Antiácidos y Digestivos'),
                'laboratory_id'     => $lab('Bagó'),
                'active_ingredient' => 'Omeprazol',
                'unit_id'           => $unit('Cápsula'),
                'purchase_price'         => 0.30,  'unit_sale_price'        => 0.70,
                'package_purchase_price' => 2.50,  'package_sale_price'     => 6.00, 'units_per_package' => 10,
                'stock_actual' => 450, 'stock_minimum' => 50, 'stock_maximum' => 900,
                'taxed_product' => false, 'requires_recipe' => false,
                'expiration_date' => '2026-11-30', 'location' => 'D-01', 'status' => 1,
            ],
            // Antidiabéticos
            [
                'came'              => 'Metformina 500mg',
                'description'       => 'Antidiabético oral de primera línea (biguanida)',
                'category_id'       => $cat('Antidiabéticos'),
                'laboratory_id'     => $lab('Abbott'),
                'active_ingredient' => 'Metformina clorhidrato',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.20,  'unit_sale_price'        => 0.50,
                'package_purchase_price' => 1.80,  'package_sale_price'     => 4.50, 'units_per_package' => 10,
                'stock_actual' => 700, 'stock_minimum' => 80, 'stock_maximum' => 1400,
                'taxed_product' => false, 'requires_recipe' => true,
                'expiration_date' => '2027-05-31', 'location' => 'E-01', 'status' => 1,
            ],
            // Vitaminas
            [
                'came'              => 'Vitamina C 500mg',
                'description'       => 'Ácido ascórbico, antioxidante y estimulante inmune',
                'category_id'       => $cat('Vitaminas y Suplementos'),
                'laboratory_id'     => $lab('GlaxoSmithKline'),
                'active_ingredient' => 'Ácido ascórbico',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.15,  'unit_sale_price'        => 0.35,
                'package_purchase_price' => 1.20,  'package_sale_price'     => 3.00, 'units_per_package' => 10,
                'stock_actual' => 800, 'stock_minimum' => 100, 'stock_maximum' => 2000,
                'taxed_product' => false, 'requires_recipe' => false,
                'expiration_date' => '2027-09-30', 'location' => 'F-01', 'status' => 1,
            ],
            [
                'came'              => 'Vitamina B12 1000mcg',
                'description'       => 'Cianocobalamina para anemia y sistema nervioso',
                'category_id'       => $cat('Vitaminas y Suplementos'),
                'laboratory_id'     => $lab('Roche'),
                'active_ingredient' => 'Cianocobalamina',
                'unit_id'           => $unit('Ampolla'),
                'purchase_price'         => 1.50,  'unit_sale_price'        => 3.50,
                'package_purchase_price' => null,  'package_sale_price'     => null, 'units_per_package' => null,
                'stock_actual' => 150, 'stock_minimum' => 20, 'stock_maximum' => 300,
                'taxed_product' => false, 'requires_recipe' => false,
                'expiration_date' => '2026-08-31', 'location' => 'F-02', 'status' => 1,
            ],
            // Dermatológicos
            [
                'came'              => 'Diclofenaco Gel 1% 50g',
                'description'       => 'Antiinflamatorio tópico para dolor muscular y articular',
                'category_id'       => $cat('Dermatológicos'),
                'laboratory_id'     => $lab('Bayer'),
                'active_ingredient' => 'Diclofenaco dietilamonio',
                'unit_id'           => $unit('Tubo'),
                'purchase_price'         => 8.00,  'unit_sale_price'        => 15.00,
                'package_purchase_price' => null,  'package_sale_price'     => null, 'units_per_package' => null,
                'stock_actual' => 80, 'stock_minimum' => 10, 'stock_maximum' => 200,
                'taxed_product' => true, 'requires_recipe' => false,
                'expiration_date' => '2026-07-31', 'location' => 'G-01', 'status' => 1,
            ],
            // Antihistamínicos
            [
                'came'              => 'Loratadina 10mg',
                'description'       => 'Antihistamínico de segunda generación, no sedante',
                'category_id'       => $cat('Antihistamínicos'),
                'laboratory_id'     => $lab('Medifarma'),
                'active_ingredient' => 'Loratadina',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.25,  'unit_sale_price'        => 0.60,
                'package_purchase_price' => 2.00,  'package_sale_price'     => 5.50, 'units_per_package' => 10,
                'stock_actual' => 400, 'stock_minimum' => 40, 'stock_maximum' => 800,
                'taxed_product' => false, 'requires_recipe' => false,
                'expiration_date' => '2027-02-28', 'location' => 'H-01', 'status' => 1,
            ],
            // Antiparasitarios
            [
                'came'              => 'Albendazol 400mg',
                'description'       => 'Antiparasitario de amplio espectro',
                'category_id'       => $cat('Antiparasitarios'),
                'laboratory_id'     => $lab('Farmex'),
                'active_ingredient' => 'Albendazol',
                'unit_id'           => $unit('Tableta'),
                'purchase_price'         => 0.60,  'unit_sale_price'        => 1.50,
                'package_purchase_price' => null,  'package_sale_price'     => null, 'units_per_package' => null,
                'stock_actual' => 250, 'stock_minimum' => 30, 'stock_maximum' => 500,
                'taxed_product' => false, 'requires_recipe' => false,
                'expiration_date' => '2026-10-31', 'location' => 'I-01', 'status' => 1,
            ],
            // Oftalmológicos
            [
                'came'              => 'Cloranfenicol Colirio 0.5%',
                'description'       => 'Antibiótico oftálmico para conjuntivitis bacteriana',
                'category_id'       => $cat('Oftalmológicos'),
                'laboratory_id'     => $lab('Roemmers'),
                'active_ingredient' => 'Cloranfenicol',
                'unit_id'           => $unit('Frasco'),
                'purchase_price'         => 3.50,  'unit_sale_price'        => 7.00,
                'package_purchase_price' => null,  'package_sale_price'     => null, 'units_per_package' => null,
                'stock_actual' => 100, 'stock_minimum' => 15, 'stock_maximum' => 200,
                'taxed_product' => false, 'requires_recipe' => true,
                'expiration_date' => '2026-05-31', 'location' => 'J-01', 'status' => 1,
            ],
        ];

        // Cada producto obtiene su código automático desde document_series
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
