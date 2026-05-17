<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\PurchaseRequest;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    /**
     * Lista el historial de compras de la sede del empleado.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $query = Purchase::with('items.product')
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id);

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('document_number', 'like', '%' . $request->buscar . '%')
                  ->orWhere('supplier', 'like', '%' . $request->buscar . '%');
            });
        }

        if ($request->filled('tipo')) {
            $query->where('document_type', $request->tipo);
        }

        $compras = $query->orderByDesc('purchased_at')->paginate(15)->withQueryString();

        return view('employee.pages.purchases.index', compact('compras'));
    }

    /**
     * Muestra el formulario para registrar una compra.
     */
    public function create(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        // Si se llega con ?product=CODE, precargar ese producto
        $productoCodigo = $request->query('product');
        $producto       = $productoCodigo
            ? Product::where('code', $productoCodigo)
                ->where('company_id', $employee->company_id)
                ->first()
            : null;

        $branchId  = $employee->branch_id;
        $productos = Product::where('company_id', $employee->company_id)
            ->where('status', 1)
            ->with(['branchStocks' => fn ($q) => $q->where('branch_id', $branchId)])
            ->orderBy('name')
            ->get(['code', 'name', 'purchase_price']);

        return view('employee.pages.purchases.form', compact('productos', 'producto'));
    }

    /**
     * Guarda la compra e incrementa el stock en branch_stock atómicamente.
     */
    public function store(PurchaseRequest $request)
    {
        $employee = auth()->guard('employee')->user();

        DB::beginTransaction();

        try {
            // Calcular totales
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_cost'];
            }

            $tax   = (float) ($request->tax ?? 0);
            $total = $subtotal + $tax;

            // Crear cabecera de compra
            $compra = Purchase::create([
                'company_id'      => $employee->company_id,
                'branch_id'       => $employee->branch_id,
                'employee_id'     => $employee->id,
                'document_type'   => $request->document_type,
                'document_number' => $request->document_number,
                'supplier'        => $request->supplier,
                'subtotal'        => $subtotal,
                'tax'             => $tax,
                'total'           => $total,
                'status'          => 1,
                'notes'           => $request->notes,
                'purchased_at'    => $request->purchased_at,
            ]);

            // Registrar cada ítem e incrementar stock en branch_stock
            foreach ($request->items as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_cost'];

                PurchaseDetail::create([
                    'purchase_id'     => $compra->id,
                    'product_code'    => $item['product_code'],
                    'quantity'        => $item['quantity'],
                    'unit_cost'       => $item['unit_cost'],
                    'subtotal'        => $itemSubtotal,
                    'expiration_date' => $item['expiration_date'] ?? null,
                    'batch'           => $item['batch'] ?? null,
                ]);

                // Incrementar stock en branch_stock con lockForUpdate para evitar condiciones de carrera.
                // Si no existe el registro, se crea con el stock recibido.
                $branchStock = BranchStock::where('branch_id', $employee->branch_id)
                    ->where('product_code', $item['product_code'])
                    ->lockForUpdate()
                    ->first();

                if ($branchStock) {
                    $nuevoStock = $branchStock->stock_actual + $item['quantity'];
                    $branchStock->update(['stock_actual' => $nuevoStock]);
                } else {
                    $nuevoStock  = $item['quantity'];
                    $branchStock = BranchStock::create([
                        'branch_id'    => $employee->branch_id,
                        'product_code' => $item['product_code'],
                        'stock_actual' => $nuevoStock,
                    ]);
                }

                // Registrar movimiento en el kardex
                StockMovement::create([
                    'company_id'     => $employee->company_id,
                    'branch_id'      => $employee->branch_id,
                    'product_code'   => $item['product_code'],
                    'type'           => 'entrada',
                    'reference_type' => 'purchase',
                    'reference_id'   => $compra->id,
                    'quantity'       => (int) $item['quantity'],
                    'unit_cost'      => $item['unit_cost'],
                    'balance'        => (int) $nuevoStock,
                ]);
            }

            DB::commit();

            return redirect()->route('employee.purchases.index')
                ->with('success', 'Compra registrada correctamente. El stock fue actualizado.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar compra: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al registrar la compra. Por favor, inténtelo de nuevo.');
        }
    }

    /**
     * Muestra el detalle de una compra.
     */
    public function show(Purchase $purchase)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($purchase->company_id !== $employee->company_id, 403);

        $purchase->load('items.product');
        return view('employee.pages.purchases.show', compact('purchase'));
    }
}
