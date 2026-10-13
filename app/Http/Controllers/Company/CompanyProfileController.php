<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyProfileRequest;

class CompanyProfileController extends Controller
{
    /**
     * Muestra los datos generales de la empresa (el nombre se ve en la barra lateral de los paneles).
     */
    public function edit()
    {
        return view('company.pages.profile.form', ['company' => auth()->guard('company')->user()]);
    }

    /**
     * Guarda los datos generales de la empresa.
     */
    public function update(CompanyProfileRequest $request)
    {
        auth()->guard('company')->user()->update($request->validated());

        return redirect()->route('company.profile.edit')->with('success', 'Datos de la empresa actualizados correctamente.');
    }
}
