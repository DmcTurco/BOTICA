<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $query = Category::query()
            ->where('company_id', $employee->company_id)
            ->withCount('productos');

        // Aplicar filtros de búsqueda
        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->buscar . '%')
                    ->orWhere('description', 'like', '%' . $request->buscar . '%');
            });
        }

        if ($request->filled('estado')) {
            $query->where('status', $request->estado);
        }

        // Ordenar y paginar resultados
        $categories = $query->orderBy('name', 'asc')
            ->paginate(9)
            ->withQueryString();

        return view('employee.pages.category.index', compact('categories'));
    }

    public function create()
    {
        return view('employee.pages.category.form');
    }

    public function store(CategoryRequest $request)
    {
        $employee = auth()->guard('employee')->user();

        try {
            $categoria = new Category();
            $categoria->company_id   = $employee->company_id;
            $categoria->name         = $request->nombre;
            $categoria->description  = $request->descripcion;
            $categoria->icon         = $request->icono;
            $categoria->status       = $request->has('activo') ? 1 : 0;
            $categoria->save();

            return redirect()->route('employee.categories.index')
                ->with('success', 'Categoría creada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear categoría: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear la categoría. Por favor, inténtelo de nuevo.');
        }
    }


    public function edit($id)
    {
        $employee  = auth()->guard('employee')->user();
        $categoria = Category::where('company_id', $employee->company_id)->findOrFail($id);
        $productosCount = $categoria->productos()->count();

        return view('employee.pages.category.form', compact('categoria', 'productosCount'));
    }


    public function update(CategoryRequest $request, $id)
    {
        $employee  = auth()->guard('employee')->user();
        $categoria = Category::where('company_id', $employee->company_id)->findOrFail($id);

        try {
            $categoria->name = $request->nombre;
            $categoria->description = $request->descripcion;
            $categoria->icon = $request->icono;
            $categoria->status = $request->has('activo') ? 1 : 0;
            $categoria->save();

            return redirect()->route('employee.categories.index')
                ->with('success', 'Categoría actualizada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar categoría: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar la categoría. Por favor, inténtelo de nuevo.');
        }
    }

    public function destroy($id)
    {
        $employee  = auth()->guard('employee')->user();
        $categoria = Category::where('company_id', $employee->company_id)->findOrFail($id);

        try {
            $productosCount = $categoria->productos()->count();
            if ($productosCount > 0) {
                return redirect()->route('employee.categories.index')
                    ->with('error', 'No se puede eliminar la categoría porque tiene productos asociados.');
            }
            $categoria->delete();
            return redirect()->route('employee.categories.index')
                ->with('success', 'Categoría eliminada correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('employee.categories.index')
                ->with('error', 'Ha ocurrido un error al intentar eliminar la categoría: ' . $e->getMessage());
        }
    }
}
