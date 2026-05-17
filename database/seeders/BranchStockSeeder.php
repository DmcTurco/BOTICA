<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchStockSeeder extends Seeder
{
    /**
     * Carga el stock inicial de todos los productos para la Sede Principal (branch_id = 1).
     * En producción, el stock se gestiona vía compras (purchases) y ajustes (stock_movements).
     */
    public function run(): void
    {
        $now      = now();
        $branchId = 1; // Sede Principal

        // Stock inicial por producto (buscamos el code generado por DocumentSeries)
        $stock = [
            ['name' => 'Paracetamol 500mg',        'stock_actual' => 500, 'stock_minimum' =>  50, 'stock_maximum' => 1000],
            ['name' => 'Ibuprofeno 400mg',          'stock_actual' => 400, 'stock_minimum' =>  40, 'stock_maximum' =>  800],
            ['name' => 'Naproxeno 550mg',           'stock_actual' => 300, 'stock_minimum' =>  30, 'stock_maximum' =>  600],
            ['name' => 'Amoxicilina 500mg',         'stock_actual' => 350, 'stock_minimum' =>  50, 'stock_maximum' =>  700],
            ['name' => 'Azitromicina 500mg',        'stock_actual' => 200, 'stock_minimum' =>  30, 'stock_maximum' =>  400],
            ['name' => 'Amlodipino 5mg',            'stock_actual' => 600, 'stock_minimum' =>  60, 'stock_maximum' => 1200],
            ['name' => 'Enalapril 10mg',            'stock_actual' => 550, 'stock_minimum' =>  60, 'stock_maximum' => 1100],
            ['name' => 'Omeprazol 20mg',            'stock_actual' => 450, 'stock_minimum' =>  50, 'stock_maximum' =>  900],
            ['name' => 'Metformina 500mg',          'stock_actual' => 700, 'stock_minimum' =>  80, 'stock_maximum' => 1400],
            ['name' => 'Vitamina C 500mg',          'stock_actual' => 800, 'stock_minimum' => 100, 'stock_maximum' => 2000],
            ['name' => 'Vitamina B12 1000mcg',      'stock_actual' => 150, 'stock_minimum' =>  20, 'stock_maximum' =>  300],
            ['name' => 'Diclofenaco Gel 1% 50g',   'stock_actual' =>  80, 'stock_minimum' =>  10, 'stock_maximum' =>  200],
            ['name' => 'Loratadina 10mg',           'stock_actual' => 400, 'stock_minimum' =>  40, 'stock_maximum' =>  800],
            ['name' => 'Albendazol 400mg',          'stock_actual' => 250, 'stock_minimum' =>  30, 'stock_maximum' =>  500],
            ['name' => 'Cloranfenicol Colirio 0.5%','stock_actual' => 100, 'stock_minimum' =>  15, 'stock_maximum' =>  200],
        ];

        foreach ($stock as $item) {
            $productCode = DB::table('products')->where('name', $item['name'])->value('code');

            if (!$productCode) {
                throw new \RuntimeException(
                    "Producto no encontrado: '{$item['name']}'. Asegúrate de correr ProductSeeder primero."
                );
            }

            DB::table('branch_stock')->insert([
                'branch_id'    => $branchId,
                'product_code' => $productCode,
                'stock_actual' => $item['stock_actual'],
                'stock_minimum'=> $item['stock_minimum'],
                'stock_maximum'=> $item['stock_maximum'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }
}
