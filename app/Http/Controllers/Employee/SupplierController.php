<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Lista los proveedores con su deuda pendiente.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $query = Supplier::where('company_id', $employee->company_id)->withCount('purchases');

        if ($request->filled('buscar')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%' . $request->buscar . '%')
                ->orWhere('ruc', 'like', '%' . $request->buscar . '%')
                ->orWhere('contact', 'like', '%' . $request->buscar . '%'));
        }

        $suppliers = $query->orderBy('name')->paginate(15)->withQueryString();

        // Deuda pendiente por proveedor (compras a crédito vigentes)
        $debts = \App\Models\Purchase::where('company_id', $employee->company_id)
            ->where('payment_condition', 'credit')->where('status', 1)
            ->whereIn('supplier_id', $suppliers->pluck('id'))
            ->selectRaw('supplier_id, SUM(total - paid_amount) as debt')
            ->groupBy('supplier_id')->pluck('debt', 'supplier_id');

        return view('employee.pages.suppliers.index', compact('suppliers', 'debts'));
    }

    /**
     * Registra un proveedor.
     */
    public function store(SupplierRequest $request)
    {
        $employee = auth()->guard('employee')->user();

        Supplier::create(['company_id' => $employee->company_id] + $request->validated());

        return redirect()->route('employee.suppliers.index')->with('success', 'Proveedor registrado correctamente.');
    }

    /**
     * Actualiza un proveedor.
     */
    public function update(SupplierRequest $request, Supplier $supplier)
    {
        abort_if($supplier->company_id !== auth()->guard('employee')->user()->company_id, 403);

        $supplier->update($request->validated());

        return redirect()->route('employee.suppliers.index')->with('success', 'Proveedor actualizado correctamente.');
    }

    /**
     * Elimina un proveedor (soft delete). Si tiene deuda pendiente no se puede eliminar.
     */
    public function destroy(Supplier $supplier)
    {
        abort_if($supplier->company_id !== auth()->guard('employee')->user()->company_id, 403);

        $debt = $supplier->purchases()->where('payment_condition', 'credit')->where('status', 1)->get()->sum->balance;

        if ($debt > 0) {
            return redirect()->route('employee.suppliers.index')
                ->with('error', 'No se puede eliminar «' . $supplier->name . '»: tiene una deuda pendiente de S/ ' . number_format($debt, 2) . '. Puedes desactivarlo.');
        }

        $supplier->delete();

        return redirect()->route('employee.suppliers.index')->with('success', 'Proveedor eliminado correctamente.');
    }
}
