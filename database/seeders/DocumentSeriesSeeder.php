<?php

namespace Database\Seeders;

use App\Models\DocumentSeries;
use Illuminate\Database\Seeder;

class DocumentSeriesSeeder extends Seeder
{
    /**
     * Siembra las series iniciales para todos los tipos de documento del sistema.
     *
     * Comprobantes de venta (BOLETA, FACTURA, NOTA_VENTA y notas de crédito): se crean por sede en BranchSeeder.
     *
     * Documentos internos (6 dígitos), definidos en DocumentSeries::GLOBAL:
     *   PRODUCTO       → P-000001
     *   PROVEEDOR      → X-000001
     *   COMPRA         → CMP-000001
     *   CLIENTE        → C-000001
     *   CREDITO_FIADO  → CRD-000001
     *   CIERRE_CAJA    → CIR-000001
     */
    public function run(): void
    {
        // Idempotente: se puede volver a correr sin duplicar correlativos
        DocumentSeries::crearSeriesGlobales();
    }
}
