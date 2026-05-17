<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PresentationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Resuelve el code del producto buscando por nombre
        $prod = fn(string $name) => DB::table('products')->where('name', $name)->value('code');
        $unit = fn(string $n)    => DB::table('units')->where('name', $n)->value('id');

        $presentations = [

            // ── Paracetamol 500mg ─────────────────────────────────────────
            ['product' => 'Paracetamol 500mg',    'unit' => 'Tableta', 'equivalent_amount' =>   1, 'sale_price' =>  0.50, 'main' => true],
            ['product' => 'Paracetamol 500mg',    'unit' => 'Blíster', 'equivalent_amount' =>  10, 'sale_price' =>  4.00, 'main' => false],
            ['product' => 'Paracetamol 500mg',    'unit' => 'Caja',    'equivalent_amount' => 100, 'sale_price' => 35.00, 'main' => false],

            // ── Ibuprofeno 400mg ──────────────────────────────────────────
            ['product' => 'Ibuprofeno 400mg',     'unit' => 'Tableta', 'equivalent_amount' =>   1, 'sale_price' =>  0.70, 'main' => true],
            ['product' => 'Ibuprofeno 400mg',     'unit' => 'Blíster', 'equivalent_amount' =>  10, 'sale_price' =>  6.00, 'main' => false],
            ['product' => 'Ibuprofeno 400mg',     'unit' => 'Caja',    'equivalent_amount' => 100, 'sale_price' => 55.00, 'main' => false],

            // ── Naproxeno 550mg ───────────────────────────────────────────
            ['product' => 'Naproxeno 550mg',      'unit' => 'Tableta', 'equivalent_amount' =>   1, 'sale_price' =>  1.20, 'main' => true],
            ['product' => 'Naproxeno 550mg',      'unit' => 'Blíster', 'equivalent_amount' =>  10, 'sale_price' => 10.00, 'main' => false],

            // ── Amoxicilina 500mg ─────────────────────────────────────────
            ['product' => 'Amoxicilina 500mg',    'unit' => 'Cápsula', 'equivalent_amount' =>   1, 'sale_price' =>  0.90, 'main' => true],
            ['product' => 'Amoxicilina 500mg',    'unit' => 'Blíster', 'equivalent_amount' =>  10, 'sale_price' =>  7.50, 'main' => false],

            // ── Azitromicina 500mg ────────────────────────────────────────
            ['product' => 'Azitromicina 500mg',   'unit' => 'Tableta', 'equivalent_amount' =>   1, 'sale_price' =>  2.80, 'main' => true],
            ['product' => 'Azitromicina 500mg',   'unit' => 'Tira',    'equivalent_amount' =>   6, 'sale_price' => 15.00, 'main' => false],

            // ── Amlodipino 5mg ────────────────────────────────────────────
            ['product' => 'Amlodipino 5mg',       'unit' => 'Tableta', 'equivalent_amount' =>   1, 'sale_price' =>  0.60, 'main' => true],
            ['product' => 'Amlodipino 5mg',       'unit' => 'Blíster', 'equivalent_amount' =>  10, 'sale_price' =>  5.50, 'main' => false],
            ['product' => 'Amlodipino 5mg',       'unit' => 'Caja',    'equivalent_amount' =>  30, 'sale_price' => 15.00, 'main' => false],

            // ── Enalapril 10mg ────────────────────────────────────────────
            ['product' => 'Enalapril 10mg',       'unit' => 'Tableta', 'equivalent_amount' =>   1, 'sale_price' =>  0.40, 'main' => true],
            ['product' => 'Enalapril 10mg',       'unit' => 'Blíster', 'equivalent_amount' =>  10, 'sale_price' =>  3.50, 'main' => false],

            // ── Omeprazol 20mg ────────────────────────────────────────────
            ['product' => 'Omeprazol 20mg',       'unit' => 'Cápsula', 'equivalent_amount' =>   1, 'sale_price' =>  0.70, 'main' => true],
            ['product' => 'Omeprazol 20mg',       'unit' => 'Blíster', 'equivalent_amount' =>  14, 'sale_price' =>  9.00, 'main' => false],

            // ── Metformina 500mg ──────────────────────────────────────────
            ['product' => 'Metformina 500mg',     'unit' => 'Tableta', 'equivalent_amount' =>   1, 'sale_price' =>  0.50, 'main' => true],
            ['product' => 'Metformina 500mg',     'unit' => 'Blíster', 'equivalent_amount' =>  10, 'sale_price' =>  4.50, 'main' => false],
            ['product' => 'Metformina 500mg',     'unit' => 'Caja',    'equivalent_amount' => 100, 'sale_price' => 40.00, 'main' => false],

            // ── Vitamina C 500mg ──────────────────────────────────────────
            ['product' => 'Vitamina C 500mg',     'unit' => 'Tableta', 'equivalent_amount' =>   1, 'sale_price' =>  0.35, 'main' => true],
            ['product' => 'Vitamina C 500mg',     'unit' => 'Blíster', 'equivalent_amount' =>  10, 'sale_price' =>  3.00, 'main' => false],
            ['product' => 'Vitamina C 500mg',     'unit' => 'Caja',    'equivalent_amount' => 100, 'sale_price' => 28.00, 'main' => false],

            // ── Vitamina B12 (ampollas: solo unidad) ─────────────────────
            ['product' => 'Vitamina B12 1000mcg', 'unit' => 'Ampolla', 'equivalent_amount' =>   1, 'sale_price' =>  3.50, 'main' => true],

            // ── Diclofenaco Gel (tubos: solo unidad) ─────────────────────
            ['product' => 'Diclofenaco Gel 1% 50g','unit' => 'Tubo',  'equivalent_amount' =>   1, 'sale_price' => 15.00, 'main' => true],

            // ── Loratadina 10mg ───────────────────────────────────────────
            ['product' => 'Loratadina 10mg',      'unit' => 'Tableta', 'equivalent_amount' =>   1, 'sale_price' =>  0.60, 'main' => true],
            ['product' => 'Loratadina 10mg',      'unit' => 'Blíster', 'equivalent_amount' =>  10, 'sale_price' =>  5.50, 'main' => false],

            // ── Albendazol 400mg ──────────────────────────────────────────
            ['product' => 'Albendazol 400mg',     'unit' => 'Tableta', 'equivalent_amount' =>   1, 'sale_price' =>  1.50, 'main' => true],
            ['product' => 'Albendazol 400mg',     'unit' => 'Tira',    'equivalent_amount' =>   2, 'sale_price' =>  2.80, 'main' => false],

            // ── Cloranfenicol Colirio (frasco: solo unidad) ───────────────
            ['product' => 'Cloranfenicol Colirio 0.5%', 'unit' => 'Frasco', 'equivalent_amount' => 1, 'sale_price' => 7.00, 'main' => true],
        ];

        foreach ($presentations as $p) {
            $code = $prod($p['product']);

            if (!$code) {
                throw new \RuntimeException(
                    "Producto no encontrado en BD: '{$p['product']}'. Verifica que el ProductSeeder corrió correctamente."
                );
            }

            DB::table('presentations')->insert([
                'product_code'      => $code,
                'unit_id'           => $unit($p['unit']),
                'equivalent_amount' => $p['equivalent_amount'],
                'sale_price'        => $p['sale_price'],
                'main_presentation' => $p['main'],
                'status'            => 1,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
        }
    }
}
