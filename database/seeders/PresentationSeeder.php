<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PresentationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $unit = fn(string $n) => DB::table('units')->where('name', $n)->value('id');

        // Cada producto tiene su presentación principal (unidad base)
        // Los productos en tabletas/cápsulas también tienen blíster (x10) y caja (x100)
        $presentations = [

            // ── Paracetamol 500mg ─────────────────────────────────────────
            ['product_code' => 'PAR-500', 'unit' => 'Tableta',  'equivalent_amount' =>   1, 'sale_price' =>  0.50, 'main' => true],
            ['product_code' => 'PAR-500', 'unit' => 'Blíster',  'equivalent_amount' =>  10, 'sale_price' =>  4.00, 'main' => false],
            ['product_code' => 'PAR-500', 'unit' => 'Caja',     'equivalent_amount' => 100, 'sale_price' => 35.00, 'main' => false],

            // ── Ibuprofeno 400mg ──────────────────────────────────────────
            ['product_code' => 'IBU-400', 'unit' => 'Tableta',  'equivalent_amount' =>   1, 'sale_price' =>  0.70, 'main' => true],
            ['product_code' => 'IBU-400', 'unit' => 'Blíster',  'equivalent_amount' =>  10, 'sale_price' =>  6.00, 'main' => false],
            ['product_code' => 'IBU-400', 'unit' => 'Caja',     'equivalent_amount' => 100, 'sale_price' => 55.00, 'main' => false],

            // ── Naproxeno 550mg ───────────────────────────────────────────
            ['product_code' => 'NAP-550', 'unit' => 'Tableta',  'equivalent_amount' =>   1, 'sale_price' =>  1.20, 'main' => true],
            ['product_code' => 'NAP-550', 'unit' => 'Blíster',  'equivalent_amount' =>  10, 'sale_price' => 10.00, 'main' => false],

            // ── Amoxicilina 500mg ─────────────────────────────────────────
            ['product_code' => 'AMX-500', 'unit' => 'Cápsula',  'equivalent_amount' =>   1, 'sale_price' =>  0.90, 'main' => true],
            ['product_code' => 'AMX-500', 'unit' => 'Blíster',  'equivalent_amount' =>  10, 'sale_price' =>  7.50, 'main' => false],

            // ── Azitromicina 500mg ────────────────────────────────────────
            ['product_code' => 'AZI-500', 'unit' => 'Tableta',  'equivalent_amount' =>   1, 'sale_price' =>  2.80, 'main' => true],
            ['product_code' => 'AZI-500', 'unit' => 'Tira',     'equivalent_amount' =>   6, 'sale_price' => 15.00, 'main' => false],

            // ── Amlodipino 5mg ────────────────────────────────────────────
            ['product_code' => 'AML-5',   'unit' => 'Tableta',  'equivalent_amount' =>   1, 'sale_price' =>  0.60, 'main' => true],
            ['product_code' => 'AML-5',   'unit' => 'Blíster',  'equivalent_amount' =>  10, 'sale_price' =>  5.50, 'main' => false],
            ['product_code' => 'AML-5',   'unit' => 'Caja',     'equivalent_amount' =>  30, 'sale_price' => 15.00, 'main' => false],

            // ── Enalapril 10mg ────────────────────────────────────────────
            ['product_code' => 'ENA-10',  'unit' => 'Tableta',  'equivalent_amount' =>   1, 'sale_price' =>  0.40, 'main' => true],
            ['product_code' => 'ENA-10',  'unit' => 'Blíster',  'equivalent_amount' =>  10, 'sale_price' =>  3.50, 'main' => false],

            // ── Omeprazol 20mg ────────────────────────────────────────────
            ['product_code' => 'OME-20',  'unit' => 'Cápsula',  'equivalent_amount' =>   1, 'sale_price' =>  0.70, 'main' => true],
            ['product_code' => 'OME-20',  'unit' => 'Blíster',  'equivalent_amount' =>  14, 'sale_price' =>  9.00, 'main' => false],

            // ── Metformina 500mg ──────────────────────────────────────────
            ['product_code' => 'MET-500', 'unit' => 'Tableta',  'equivalent_amount' =>   1, 'sale_price' =>  0.50, 'main' => true],
            ['product_code' => 'MET-500', 'unit' => 'Blíster',  'equivalent_amount' =>  10, 'sale_price' =>  4.50, 'main' => false],
            ['product_code' => 'MET-500', 'unit' => 'Caja',     'equivalent_amount' => 100, 'sale_price' => 40.00, 'main' => false],

            // ── Vitamina C 500mg ──────────────────────────────────────────
            ['product_code' => 'VIT-C500','unit' => 'Tableta',  'equivalent_amount' =>   1, 'sale_price' =>  0.35, 'main' => true],
            ['product_code' => 'VIT-C500','unit' => 'Blíster',  'equivalent_amount' =>  10, 'sale_price' =>  3.00, 'main' => false],
            ['product_code' => 'VIT-C500','unit' => 'Caja',     'equivalent_amount' => 100, 'sale_price' => 28.00, 'main' => false],

            // ── Vitamina B12 (ampollas: solo unidad) ─────────────────────
            ['product_code' => 'VIT-B12', 'unit' => 'Ampolla',  'equivalent_amount' =>   1, 'sale_price' =>  3.50, 'main' => true],

            // ── Diclofenaco Gel (tubos: solo unidad) ─────────────────────
            ['product_code' => 'DIC-GEL', 'unit' => 'Tubo',     'equivalent_amount' =>   1, 'sale_price' => 15.00, 'main' => true],

            // ── Loratadina 10mg ───────────────────────────────────────────
            ['product_code' => 'LOT-10',  'unit' => 'Tableta',  'equivalent_amount' =>   1, 'sale_price' =>  0.60, 'main' => true],
            ['product_code' => 'LOT-10',  'unit' => 'Blíster',  'equivalent_amount' =>  10, 'sale_price' =>  5.50, 'main' => false],

            // ── Albendazol 400mg ──────────────────────────────────────────
            ['product_code' => 'ALB-400', 'unit' => 'Tableta',  'equivalent_amount' =>   1, 'sale_price' =>  1.50, 'main' => true],
            ['product_code' => 'ALB-400', 'unit' => 'Tira',     'equivalent_amount' =>   2, 'sale_price' =>  2.80, 'main' => false],

            // ── Cloranfenicol Colirio (frasco: solo unidad) ───────────────
            ['product_code' => 'CLO-EYE', 'unit' => 'Frasco',   'equivalent_amount' =>   1, 'sale_price' =>  7.00, 'main' => true],
        ];

        foreach ($presentations as $p) {
            DB::table('presentations')->insert([
                'product_code'       => $p['product_code'],
                'unit_id'            => $unit($p['unit']),
                'equivalent_amount'  => $p['equivalent_amount'],
                'sale_price'         => $p['sale_price'],
                'main_presentation'  => $p['main'],
                'status'             => 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
        }
    }
}
