<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DocumentSeries;
use Illuminate\Database\Seeder;

class DocumentSeriesSeeder extends Seeder
{
    /**
     * Siembra las series iniciales del sistema. Idempotente: no duplica lo que ya existe.
     *
     * Documentos internos (6 dígitos), definidos en DocumentSeries::GLOBAL:
     *   PRODUCTO → P-000001 · PROVEEDOR → X-000001 · CLIENTE → C-000001
     *   COMPRA → CMP-000001 · CREDITO_FIADO → CRD-000001 · CIERRE_CAJA → CIR-000001
     *
     * Comprobantes de venta (B001, F001, NV01, BC01, FC01): se crean para cada sede existente.
     * Las sedes nuevas los reciben al crearse desde el panel.
     */
    public function run(): void
    {
        DocumentSeries::crearSeriesGlobales();

        foreach (Branch::orderBy('id')->get() as $branch) {
            DocumentSeries::crearSeriesParaSede($branch);
        }
    }
}
