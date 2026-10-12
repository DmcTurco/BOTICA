<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    /**
     * Órdenes de compra de la sede.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $query = PurchaseOrder::with('supplier:id,name')->withCount('items')
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id);

        if ($request->filled('estado')) {
            $query->where('status', $request->estado);
        }

        $orders = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('employee.pages.purchase-orders.index', compact('orders'));
    }

    /**
     * Formulario de nueva orden. Puede llegar con productos sugeridos desde Reposición
     * (?products[CODIGO]=cantidad).
     */
    public function create(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $products = Product::with('unit:id,abbreviation')
            ->where('company_id', $employee->company_id)->where('status', 1)
            ->orderBy('name')->get(['code', 'name', 'unit_id', 'purchase_price'])
            ->map(fn ($p) => ['code' => $p->code, 'name' => $p->name, 'unit' => $p->unit?->abbreviation, 'stock' => null, 'cost' => (float) $p->purchase_price])
            ->values();

        // Productos sugeridos que sí existen en el catálogo
        $suggested = collect($request->query('products', []))
            ->filter(fn ($qty, $code) => $products->contains('code', (string) $code) && is_numeric($qty) && $qty > 0)
            ->map(fn ($qty, $code) => ['product_code' => (string) $code, 'quantity' => (float) $qty])
            ->values()->all();

        return view('employee.pages.purchase-orders.form', [
            'suppliers' => Supplier::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get(['id', 'name']),
            'products'  => $products->all(),
            'initial'   => old('items', $suggested),
            'costs'     => $products->pluck('cost', 'code'),
        ]);
    }

    /**
     * Guarda la orden con sus productos y costos estimados.
     */
    public function store(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $request->validate([
            'supplier_id'           => ['required', Rule::exists('suppliers', 'id')->where('company_id', $employee->company_id)->whereNull('deleted_at')],
            'expected_date'         => 'nullable|date|after_or_equal:today',
            'notes'                 => 'nullable|string|max:500',
            'items'                 => 'required|array|min:1',
            'items.*.product_code'  => ['required', 'distinct', Rule::exists('products', 'code')->where('company_id', $employee->company_id)->whereNull('deleted_at')],
            'items.*.quantity'      => 'required|numeric|min:0.01|max:99999999',
            'items.*.unit_cost'     => 'nullable|numeric|min:0',
        ], [
            'supplier_id.required'  => 'Selecciona el proveedor.',
            'items.required'        => 'Agrega al menos un producto.',
            'items.*.product_code.distinct' => 'Un producto está repetido en la orden.',
        ]);

        $costs = Product::whereIn('code', array_column($request->items, 'product_code'))->pluck('purchase_price', 'code');

        $order = DB::transaction(function () use ($request, $employee, $costs) {
            $order = PurchaseOrder::create([
                'company_id'    => $employee->company_id,
                'branch_id'     => $employee->branch_id,
                'supplier_id'   => $request->supplier_id,
                'employee_id'   => $employee->id,
                'number'        => PurchaseOrder::nextNumber($employee->company_id),
                'expected_date' => $request->expected_date,
                'notes'         => $request->notes,
            ]);

            $total = 0;
            foreach ($request->items as $item) {
                $cost = isset($item['unit_cost']) && $item['unit_cost'] !== '' ? (float) $item['unit_cost'] : (float) ($costs[$item['product_code']] ?? 0);
                $order->items()->create(['product_code' => $item['product_code'], 'quantity' => $item['quantity'], 'unit_cost' => $cost]);
                $total += round($item['quantity'] * $cost, 2);
            }
            $order->update(['total' => $total]);

            return $order;
        });

        return redirect()->route('employee.purchase-orders.show', $order)->with('success', 'Orden de compra ' . $order->number . ' emitida.');
    }

    /**
     * Detalle de la orden (se puede imprimir y enviar al proveedor).
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOrder($purchaseOrder);

        $purchaseOrder->load(['supplier', 'items.product.unit', 'employee', 'branch']);

        return view('employee.pages.purchase-orders.show', ['order' => $purchaseOrder]);
    }

    /**
     * Anula una orden que aún no se recibió.
     */
    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOrder($purchaseOrder);

        if ($purchaseOrder->status !== PurchaseOrder::PENDING) {
            return back()->with('error', 'Solo se pueden anular órdenes pendientes.');
        }

        $purchaseOrder->update(['status' => PurchaseOrder::CANCELLED]);

        return redirect()->route('employee.purchase-orders.show', $purchaseOrder)->with('success', 'Orden anulada.');
    }

    /** Solo órdenes de la misma compañía y sede */
    private function authorizeOrder(PurchaseOrder $order): void
    {
        $employee = auth()->guard('employee')->user();

        abort_if($order->company_id !== $employee->company_id || $order->branch_id !== $employee->branch_id, 403);
    }
}
