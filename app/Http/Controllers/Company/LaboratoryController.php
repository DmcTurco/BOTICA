<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Laboratory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LaboratoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Laboratory::withCount('productos');

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

        return view('company.pages.laboratories.index', compact('laboratorios'));
    }

    public function create()
    {
        return view('company.pages.laboratories.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|max:100|unique:laboratories,name',
            'descripcion' => 'nullable|max:255',
            'pais'        => 'nullable|max:80',
            'telefono'    => 'nullable|max:30',
            'email'       => 'nullable|email|max:100',
            'activo'      => 'nullable|boolean',
        ]);

        try {
            Laboratory::create([
                'name'        => $request->nombre,
                'description' => $request->descripcion,
                'country'     => $request->pais,
                'phone'       => $request->telefono,
                'email'       => $request->email,
                'status'      => $request->has('activo') ? 1 : 0,
            ]);

            return redirect()->route('company.laboratories.index')
                ->with('success', 'Laboratorio creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear laboratorio: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al crear el laboratorio. Inténtelo de nuevo.');
        }
    }

    public function edit($id)
    {
        $laboratorio = Laboratory::findOrFail($id);
        $productosCount = $laboratorio->productos()->count();

        return view('company.pages.laboratories.form', compact('laboratorio', 'productosCount'));
    }

    public function update(Request $request, $id)
    {
        $laboratorio = Laboratory::findOrFail($id);

        $request->validate([
            'nombre'      => 'required|max:100|unique:laboratories,name,' . $id,
            'descripcion' => 'nullable|max:255',
            'pais'        => 'nullable|max:80',
            'telefono'    => 'nullable|max:30',
            'email'       => 'nullable|email|max:100',
            'activo'      => 'nullable|boolean',
        ]);

        try {
            $laboratorio->update([
                'name'        => $request->nombre,
                'description' => $request->descripcion,
                'country'     => $request->pais,
                'phone'       => $request->telefono,
                'email'       => $request->email,
                'status'      => $request->has('activo') ? 1 : 0,
            ]);

            return redirect()->route('company.laboratories.index')
                ->with('success', 'Laboratorio actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar laboratorio: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al actualizar el laboratorio. Inténtelo de nuevo.');
        }
    }

    public function destroy($id)
    {
        $laboratorio = Laboratory::findOrFail($id);

        try {
            if ($laboratorio->productos()->count() > 0) {
                return redirect()->route('company.laboratories.index')
                    ->with('error', 'No se puede eliminar el laboratorio porque tiene productos asociados.');
            }

            $laboratorio->delete();

            return redirect()->route('company.laboratories.index')
                ->with('success', 'Laboratorio eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('company.laboratories.index')
                ->with('error', 'Error al eliminar el laboratorio: ' . $e->getMessage());
        }
    }
}
