<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySunatSetting;
use Illuminate\Database\Seeder;

class CompanySunatSettingSeeder extends Seeder
{
    /**
     * Deja la compañía seed (id = 1) lista para emitir en el ambiente de PRUEBAS (beta) de SUNAT.
     * Usa el RUC público de pruebas; en beta no se necesita certificado ni claves SOL propios
     * (el sistema usa las credenciales públicas MODDATOS y un certificado de prueba incluido).
     * Para producción, la empresa edita estos datos en el panel (Facturación SUNAT).
     */
    public function run(): void
    {
        $company = Company::find(1);

        if (!$company) {
            return;
        }

        $company->update(['ruc' => '20000000001']);

        CompanySunatSetting::updateOrCreate(['company_id' => $company->id], [
            'legal_name'     => 'EMPRESA DE PRUEBA SAC',
            'trade_name'     => 'BOTICA DE PRUEBA',
            'fiscal_address' => 'AV. PRUEBA 123, LIMA',
            'ubigeo'         => '150101',
            'department'     => 'LIMA',
            'province'       => 'LIMA',
            'district'       => 'LIMA',
            'environment'    => CompanySunatSetting::ENV_BETA,
            'enabled'        => true,
        ]);
    }
}
