<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\PurchaseRequest;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\BatchService;
use App\Services\StockService;
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

        $suppliers = Supplier::where('company_id', $employee->company_id)->where('status', 1)->orderBy('name')->get(['id', 'name', 'ruc']);

        // Recepción de una orden de compra: se precargan sus productos y el proveedor
        $order = $request->filled('order')
            ? PurchaseOrder::with('items')->where('company_id', $employee->company_id)->where('status', 'pending')->find($request->query('order'))
            : null;

        $preload = $order ? $order->items->map(fn ($i) => ['code' => $i->product_code, 'qty' => (float) $i->quantity, 'cost' => (float) $i->unit_cost])->values()->all() : [];

        return view('employee.pages.purchases.form', compact('productos', 'producto', 'suppliers', 'order', 'preload'));
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

            // Proveedor registrado (su nombre se copia al documento) y condición de pago
            $supplier         = $request->filled('supplier_id') ? Supplier::find($request->supplier_id) : null;
            $paymentCondition = $request->input('payment_condition', 'cash');

            // Crear cabecera de compra
            $compra = Purchase::create([
                'company_id'      => $employee->company_id,
                'branch_id'       => $employee->branch_id,
                'employee_id'     => $employee->id,
                'document_type'   => $request->document_type,
                'document_number' => $request->document_number,
                'supplier'        => $supplier?->name ?? $request->supplier,
                'supplier_id'     => $supplier?->id,
                'payment_condition' => $paymentCondition,
                'due_date'        => $paymentCondition === 'credit' ? $request->due_date : null,
                'paid_amount'     => $paymentCondition === 'credit' ? 0 : $total,
                'purchase_order_id' => $request->purchase_order_id,
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

                // Desglose por lote y vencimiento (para vender primero lo que vence antes)
                app(BatchService::class)->receive(
                    $employee->company_id, $employee->branch_id, $item['product_code'], (float) $item['quantity'],
                    $item['batch'] ?? null, $item['expiration_date'] ?? null, (float) $item['unit_cost'], 'purchase', $compra->id
                );

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

            // La orden de compra queda recibida
            if ($request->filled('purchase_order_id')) {
                PurchaseOrder::where('company_id', $employee->company_id)->whereKey($request->purchase_order_id)->update(['status' => PurchaseOrder::RECEIVED]);
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

        $purchase->load(['items.product', 'payments.employee', 'supplierRecord']);
        return view('employee.pages.purchases.show', compact('purchase'));
    }

    /**
     * Anula una compra (eliminar guía de ingreso): descuenta del stock lo que ingresó.
     * Solo es posible si esas unidades todavía están en la sede (no se vendieron).
     */
    public function void(Request $request, Purchase $purchase, StockService $stock)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($purchase->company_id !== $employee->company_id || $purchase->branch_id !== $employee->branch_id, 403);

        $request->validate(['void_reason' => 'required|string|max:255'], [
            'void_reason.required' => 'Indica el motivo de la anulación.',
        ]);

        if ((int) $purchase->status === 0) {
            return back()->with('error', 'Esta compra ya está anulada.');
        }

        DB::beginTransaction();

        try {
            $batches = app(BatchService::class);

            // Si parte de los lotes de esta compra ya se vendió, no se puede anular
            if (!$batches->isIntact('purchase', $purchase->id, $employee->branch_id)) {
                throw new \RuntimeException('Parte de los lotes de esta compra ya se vendió o se movió.');
            }

            foreach ($purchase->items()->orderBy('product_code')->get() as $item) {
                $stock->move(
                    $employee->company_id, $employee->branch_id, $item->product_code, -(float) $item->quantity,
                    'purchase_void', $purchase->id, (float) $item->unit_cost, 'Anulación de compra'
                );
            }

            $batches->discardSource('purchase', $purchase->id, $employee->branch_id);
            foreach ($purchase->items as $item) {
                $batches->reconcile($employee->branch_id, $item->product_code);
            }

            $purchase->update(['status' => 0, 'voided_at' => now(), 'void_reason' => $request->void_reason]);

            DB::commit();

            \App\Models\AuditLog::record('purchase.void', 'Anuló la compra #' . $purchase->id . ' (S/ ' . number_format($purchase->total, 2) . ')', 'Compra #' . $purchase->id, ['motivo' => $request->void_reason]);

            return redirect()->route('employee.purchases.show', $purchase)
                ->with('success', 'Compra anulada. El stock fue descontado.');
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->with('error', 'No se puede anular: parte de esa mercadería ya no está en stock. ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al anular compra: ' . $e->getMessage());
            return back()->with('error', 'Error al anular la compra. Inténtelo de nuevo.');
        }
    }
}
