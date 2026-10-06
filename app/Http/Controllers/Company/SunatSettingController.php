<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\SunatSettingRequest;
use App\Models\CompanySunatSetting;
use App\Services\Sunat\CertificateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SunatSettingController extends Controller
{
    /**
     * Muestra la configuración de facturación electrónica de la compañía.
     */
    public function edit()
    {
        $company = auth()->guard('company')->user();

        $setting = CompanySunatSetting::firstOrNew(['company_id' => $company->id]);
        $setting->setRelation('company', $company);

        return view('company.pages.sunat.form', compact('company', 'setting'));
    }

    /**
     * Guarda los datos fiscales, las credenciales SOL y el certificado digital.
     * Las claves en blanco conservan el valor ya guardado.
     */
    public function update(SunatSettingRequest $request, CertificateService $certificates)
    {
        $company = auth()->guard('company')->user();

        $data = $request->safe()->only([
            'legal_name', 'trade_name', 'fiscal_address', 'ubigeo',
            'department', 'province', 'district', 'environment', 'boleta_mode', 'sol_user',
        ]);
        $data['enabled'] = $request->boolean('enabled');

        // Solo se reemplaza la clave SOL si se escribió una nueva
        if ($request->filled('sol_password')) {
            $data['sol_password'] = $request->input('sol_password');
        }

        // Si se escribió un usuario SOL vacío, no se borra el anterior
        if (!$request->filled('sol_user')) {
            unset($data['sol_user']);
        }

        try {
            // Valida y guarda el certificado antes de tocar la base de datos
            if ($request->hasFile('certificate')) {
                $data += $certificates->store(
                    $company->id,
                    $request->file('certificate')->get(),
                    $request->input('certificate_password')
                );
            }

            DB::transaction(function () use ($company, $request, $data) {
                $company->update(['ruc' => $request->input('ruc')]);

                CompanySunatSetting::updateOrCreate(['company_id' => $company->id], $data);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withInput($request->except(['sol_password', 'certificate_password']))
                ->withErrors(['certificate' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Error al guardar la configuración SUNAT: ' . $e->getMessage());

            return back()->withInput($request->except(['sol_password', 'certificate_password']))
                ->with('error', 'No se pudo guardar la configuración.');
        }

        return redirect()->route('company.sunat.edit')
            ->with('success', 'Configuración de facturación electrónica guardada.');
    }
}
