<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentSeriesSeeder extends Seeder
{
    /**
     * Siembra las series iniciales para todos los tipos de documento del sistema.
     *
     * Comprobantes de venta (BOLETA, FACTURA, NOTA_VENTA): se crean por sede en BranchSeeder.
     *
     * Documentos internos (6 dígitos):
     *   PRODUCTO       → P-000001
     *   PROVEEDOR      → X-000001
     *   COMPRA         → CMP-000001
     *   CLIENTE        → C-000001
     *   CREDITO_FIADO  → CRD-000001
     *   CIERRE_CAJA    → CIR-000001
     */
    public function run(): void
    {
        $now = now();

        DB::table('document_series')->insert([

            // Los comprobantes de venta (BOLETA, FACTURA, NOTA_VENTA) se crean por sede
            // en BranchSeeder con DocumentSeries::crearSeriesParaSede().

            // ── Documentos internos (6 dígitos) ───────────────────────
            [
                'type_code'      => 'PRODUCTO',
                'name'           => 'Producto',
                'series'         => 'P',
                'current_number' => 0,
                'digits'         => 6,
                'active'         => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type_code'      => 'PROVEEDOR',
                'name'           => 'Proveedor',
                'series'         => 'X',
                'current_number' => 0,
                'digits'         => 6,
                'active'         => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type_code'      => 'CLIENTE',
                'name'           => 'Cliente',
                'series'         => 'C',
                'current_number' => 0,
                'digits'         => 6,
                'active'         => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type_code'      => 'COMPRA',
                'name'           => 'Orden de Compra',
                'series'         => 'CMP',
                'current_number' => 0,
                'digits'         => 6,
                'active'         => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type_code'      => 'CREDITO_FIADO',
                'name'           => 'Crédito Fiado',
                'series'         => 'CRD',
                'current_number' => 0,
                'digits'         => 6,
                'active'         => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type_code'      => 'CIERRE_CAJA',
                'name'           => 'Cierre de Caja',
                'series'         => 'CIR',
                'current_number' => 0,
                'digits'         => 6,
                'active'         => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type_code'      => 'NOTA_CREDITO',
                'name'           => 'Nota de Crédito',
                'series'         => 'BN01',
                'current_number' => 0,
                'digits'         => 8,
                'active'         => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ]);
    }
}
