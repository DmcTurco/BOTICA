<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Product;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    /**
     * Registro de recetas despachadas en la sede (por defecto las del mes), con los productos
     * que exigían receta en cada venta.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $desde = $request->input('fecha_desde') ?: now()->startOfMonth()->toDateString();
        $hasta = $request->input('fecha_hasta') ?: now()->endOfMonth()->toDateString();

        $query = Prescription::with(['order.items', 'employee:id,name'])
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta);

        if ($request->filled('buscar')) {
            $term = '%' . $request->buscar . '%';
            $query->where(fn ($q) => $q->where('patient_name', 'like', $term)
                ->orWhere('patient_document', 'like', $term)
                ->orWhere('doctor_name', 'like', $term)
                ->orWhere('doctor_license', 'like', $term)
                ->orWhere('prescription_number', 'like', $term));
        }

        $prescriptions = $query->orderByDesc('id')->paginate(15)->withQueryString();

        // Productos de cada venta que exigían receta
        $codes    = $prescriptions->getCollection()->flatMap(fn ($p) => $p->order?->items->pluck('product_code') ?? [])->unique();
        $products = Product::withTrashed()->whereIn('code', $codes)->get(['code', 'requires_recipe', 'controlled_type'])->keyBy('code');

        $prescriptions->getCollection()->each(function (Prescription $p) use ($products) {
            $p->recipe_items = ($p->order?->items ?? collect())
                ->filter(fn ($i) => ($products[$i->product_code]->recipe_level ?? 0) > 0)
                ->map(fn ($i) => ['name' => $i->product_name, 'qty' => (float) $i->quantity, 'controlled' => ($products[$i->product_code]->recipe_level ?? 0) === 2])
                ->values();
        });

        return view('employee.pages.prescriptions.index', compact('prescriptions', 'desde', 'hasta'));
    }
}
