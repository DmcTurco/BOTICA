<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Carbon\Exceptions\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()->withCount('productos');

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
        $categorias = $query->orderBy('name', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('company.pages.category.index', compact('categorias'));
    }

    public function create()
    {
        return view('company.pages.category.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|max:100|unique:categories,name',
            'descripcion' => 'nullable|max:255',
            'icono' => 'nullable|max:50',
            'activo' => 'nullable|boolean',
        ]);

        try {
            $categoria = new Category();
            $categoria->name = $request->nombre;
            $categoria->description = $request->descripcion;
            $categoria->icon = $request->icono;
            $categoria->status = $request->has('activo') ? 1 : 0;
            $categoria->save();

            return redirect()->route('company.categories.index')
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
        $categoria = Category::findOrFail($id);
        $productosCount = $categoria->productos()->count();

        return view('company.pages.category.form', compact('categoria', 'productosCount'));
    }


    public function update(Request $request, $id)
    {
        $categoria = Category::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|max:100|unique:categories,name,' . $id,
            'descripcion' => 'nullable|max:255',
            'icono' => 'nullable|max:50',
            'activo' => 'nullable|boolean',
        ]);

        try {
            $categoria->name = $request->nombre;
            $categoria->description = $request->descripcion;
            $categoria->icon = $request->icono;
            $categoria->status = $request->has('activo') ? 1 : 0;
            $categoria->save();

            return redirect()->route('company.categories.index')
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
        $categoria = Category::findOrFail($id);

        try {
            $categoria = Category::findOrFail($id);
            $productosCount = $categoria->productos()->count();
            if ($productosCount > 0) {
                return redirect()->route('company.categories.index')
                    ->with('error', 'No se puede eliminar la categoría porque tiene productos asociados.');
            }
            $categoria->delete();
            return redirect()->route('company.categories.index')
                ->with('success', 'Categoría eliminada correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('company.categories.index')
                ->with('error', 'Ha ocurrido un error al intentar eliminar la categoría: ' . $e->getMessage());
        }
    }
}
