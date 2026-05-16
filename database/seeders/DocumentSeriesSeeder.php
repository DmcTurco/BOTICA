<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentSeriesSeeder extends Seeder
{
    /**
     * Siembra las series iniciales para todos los tipos de documento del sistema.
     *
     * Documentos SUNAT (8 dígitos según reglamento):
     *   BOLETA     → B001-00000001
     *   FACTURA    → F001-00000001
     *   NOTA_VENTA → NV01-00000001
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

            // ── Comprobantes de venta (SUNAT — 8 dígitos) ────────────
            [
                'type_code'      => 'BOLETA',
                'name'           => 'Boleta de Venta',
                'series'         => 'B001',
                'current_number' => 0,
                'digits'         => 8,
                'active'         => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type_code'      => 'FACTURA',
                'name'           => 'Factura',
                'series'         => 'F001',
                'current_number' => 0,
                'digits'         => 8,
                'active'         => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'type_code'      => 'NOTA_VENTA',
                'name'           => 'Nota de Venta',
                'series'         => 'NV01',
                'current_number' => 0,
                'digits'         => 8,
                'active'         => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],

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
