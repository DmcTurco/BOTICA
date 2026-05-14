<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    /**
     * Lista el historial de compras registradas.
     */
    public function index(Request $request)
    {
        $query = Purchase::with('items.product')
            ->where('company_id', Auth::guard('company')->id());

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

        return view('company.pages.purchases.index', compact('compras'));
    }

    /**
     * Muestra el formulario para registrar una compra.
     */
    public function create(Request $request)
    {
        // Si se llega con ?product=CODE, precargar ese producto
        $productoCodigo = $request->query('product');
        $producto       = $productoCodigo ? Product::where('code', $productoCodigo)->first() : null;

        $productos = Product::where('status', 1)->orderBy('came')
            ->get(['code', 'came', 'purchase_price', 'stock_actual', 'stock_minimum']);

        return view('company.pages.purchases.form', compact('productos', 'producto'));
    }

    /**
     * Guarda la compra e incrementa el stock de cada producto atómicamente.
     */
    public function store(PurchaseRequest $request)
    {
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
                'company_id'      => Auth::guard('company')->id(),
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

            // Registrar cada ítem e incrementar stock atómicamente
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

                // Incrementar stock usando lockForUpdate para evitar condiciones de carrera
                $productoActualizado = Product::where('code', $item['product_code'])
                    ->lockForUpdate()
                    ->first();

                $productoActualizado->increment('stock_actual', $item['quantity']);

                // Registrar movimiento en el kardex
                StockMovement::create([
                    'product_code'   => $item['product_code'],
                    'type'           => 'entrada',
                    'reference_type' => 'purchase',
                    'reference_id'   => $compra->id,
                    'quantity'       => (int) $item['quantity'],
                    'unit_cost'      => $item['unit_cost'],
                    'balance'        => (int) $productoActualizado->stock_actual,
                ]);
            }

            DB::commit();

            return redirect()->route('company.purchases.index')
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
        $purchase->load('items.product');
        return view('company.pages.purchases.show', compact('purchase'));
    }
}
