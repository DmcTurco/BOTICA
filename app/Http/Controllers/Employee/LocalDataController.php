<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\LocalDataRequest;

class LocalDataController extends Controller
{
    /**
     * Formulario con los datos de la sede del empleado (nombre, dirección, teléfono, correo).
     */
    public function edit()
    {
        $branch = auth()->guard('employee')->user()->branch;

        return view('employee.pages.local.edit', compact('branch'));
    }

    /**
     * Guarda los datos de contacto de la sede (aparecen en los comprobantes impresos).
     */
    public function update(LocalDataRequest $request)
    {
        auth()->guard('employee')->user()->branch->update($request->validated());
        \App\Models\AuditLog::record('local.update', 'Actualizó los datos del local', null, $request->validated());

        return redirect()->route('employee.local.edit')->with('success', 'Datos del local actualizados.');
    }
}
