<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\LaboratoryRequest;
use App\Models\Laboratory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LaboratoryController extends Controller
{
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $query = Laboratory::where('company_id', $employee->company_id)
            ->withCount('productos');

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->buscar . '%')
                  ->orWhere('description', 'like', '%' . $request->buscar . '%')
                  ->orWhere('country', 'like', '%' . $request->buscar . '%');
            });
        }

        if ($request->filled('estado')) {
            $query->where('status', $request->estado);
        }

        $laboratorios = $query->orderBy('name')->paginate(9)->withQueryString();

        return view('employee.pages.laboratories.index', compact('laboratorios'));
    }

    public function create()
    {
        return view('employee.pages.laboratories.form');
    }

    public function store(LaboratoryRequest $request)
    {
        $employee = auth()->guard('employee')->user();

        try {
            Laboratory::create([
                'company_id'  => $employee->company_id,
                'name'        => $request->nombre,
                'description' => $request->descripcion,
                'country'     => $request->pais,
                'phone'       => $request->telefono,
                'email'       => $request->email,
                'status'      => $request->has('activo') ? 1 : 0,
            ]);

            return redirect()->route('employee.laboratories.index')
                ->with('success', 'Laboratorio creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear laboratorio: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al crear el laboratorio. Inténtelo de nuevo.');
        }
    }

    public function edit($id)
    {
        $employee    = auth()->guard('employee')->user();
        $laboratorio = Laboratory::where('company_id', $employee->company_id)->findOrFail($id);
        $productosCount = $laboratorio->productos()->count();

        return view('employee.pages.laboratories.form', compact('laboratorio', 'productosCount'));
    }

    public function update(LaboratoryRequest $request, $id)
    {
        $employee    = auth()->guard('employee')->user();
        $laboratorio = Laboratory::where('company_id', $employee->company_id)->findOrFail($id);

        try {
            $laboratorio->update([
                'name'        => $request->nombre,
                'description' => $request->descripcion,
                'country'     => $request->pais,
                'phone'       => $request->telefono,
                'email'       => $request->email,
                'status'      => $request->has('activo') ? 1 : 0,
            ]);

            return redirect()->route('employee.laboratories.index')
                ->with('success', 'Laboratorio actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar laboratorio: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al actualizar el laboratorio. Inténtelo de nuevo.');
        }
    }

    public function destroy($id)
    {
        $employee    = auth()->guard('employee')->user();
        $laboratorio = Laboratory::where('company_id', $employee->company_id)->findOrFail($id);

        try {
            if ($laboratorio->productos()->count() > 0) {
                return redirect()->route('employee.laboratories.index')
                    ->with('error', 'No se puede eliminar el laboratorio porque tiene productos asociados.');
            }

            $laboratorio->delete();

            return redirect()->route('employee.laboratories.index')
                ->with('success', 'Laboratorio eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('employee.laboratories.index')
                ->with('error', 'Error al eliminar el laboratorio: ' . $e->getMessage());
        }
    }
}
