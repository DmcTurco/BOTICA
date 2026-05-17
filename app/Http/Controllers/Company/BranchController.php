<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Lista todas las sedes de la compañía.
     */
    public function index()
    {
        $company = auth()->guard('company')->user();

        $branches = Branch::where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        return view('company.pages.branches.index', compact('branches'));
    }

    /**
     * Muestra el formulario para crear una sede.
     */
    public function create()
    {
        return view('company.pages.branches.form');
    }

    /**
     * Guarda una nueva sede.
     */
    public function store(Request $request)
    {
        $company = auth()->guard('company')->user();

        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'address' => 'nullable|string|max:200',
            'phone'   => 'nullable|string|max:30',
            'email'   => 'nullable|email|max:100',
            'status'  => 'required|in:0,1',
        ]);

        $data['company_id'] = $company->id;

        Branch::create($data);

        return redirect()->route('company.branches.index')
            ->with('success', 'Sede creada correctamente.');
    }

    /**
     * Muestra el formulario para editar una sede.
     */
    public function edit(Branch $branch)
    {
        $company = auth()->guard('company')->user();
        abort_if($branch->company_id !== $company->id, 403);

        return view('company.pages.branches.form', compact('branch'));
    }

    /**
     * Actualiza una sede existente.
     */
    public function update(Request $request, Branch $branch)
    {
        $company = auth()->guard('company')->user();
        abort_if($branch->company_id !== $company->id, 403);

        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'address' => 'nullable|string|max:200',
            'phone'   => 'nullable|string|max:30',
            'email'   => 'nullable|email|max:100',
            'status'  => 'required|in:0,1',
        ]);

        $branch->update($data);

        return redirect()->route('company.branches.index')
            ->with('success', 'Sede actualizada correctamente.');
    }

    /**
     * Elimina una sede (solo si no tiene empleados ni transacciones).
     */
    public function destroy(Branch $branch)
    {
        $company = auth()->guard('company')->user();
        abort_if($branch->company_id !== $company->id, 403);

        if ($branch->employees()->exists()) {
            return back()->with('error', 'No se puede eliminar una sede con empleados asignados.');
        }

        if ($branch->orders()->exists() || $branch->purchases()->exists()) {
            return back()->with('error', 'No se puede eliminar una sede con transacciones registradas.');
        }

        $branch->delete();

        return redirect()->route('company.branches.index')
            ->with('success', 'Sede eliminada correctamente.');
    }
}
