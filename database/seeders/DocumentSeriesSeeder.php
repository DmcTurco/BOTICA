<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DocumentSeries;
use Illuminate\Database\Seeder;

class DocumentSeriesSeeder extends Seeder
{
    /**
     * Siembra las series iniciales del sistema. Idempotente: no duplica lo que ya existe.
     *
     * Correlativos internos de cada compañía (DocumentSeries::PER_COMPANY), compartidos por sus sedes:
     *   PRODUCTO → P-00000001 · PROVEEDOR → X-00000001 · CLIENTE → C-00000001
     *   COMPRA → CMP-00000001 · CREDITO_FIADO → CRD-00000001 · CIERRE_CAJA → CIR-00000001
     *
     * Comprobantes de venta (B001, F001, NV01, BC01, FC01): se crean para cada sede existente.
     * Las sedes nuevas los reciben al crearse desde el panel.
     */
    public function run(): void
    {
        foreach (Company::orderBy('id')->pluck('id') as $companyId) {
            DocumentSeries::crearSeriesDeCompania($companyId);
        }

        foreach (Branch::orderBy('id')->get() as $branch) {
            DocumentSeries::crearSeriesParaSede($branch);
        }
    }
}
