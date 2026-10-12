<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StockTransferRequest;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\BatchService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockTransferController extends Controller
{
    /**
     * Traspasos enviados o recibidos por la sede del empleado.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $query = StockTransfer::with(['fromBranch:id,name', 'toBranch:id,name'])
            ->withCount('items')
            ->where('company_id', $employee->company_id)
            ->where(fn ($q) => $q->where('from_branch_id', $employee->branch_id)->orWhere('to_branch_id', $employee->branch_id));

        if ($request->input('direccion') === 'enviados') {
            $query->where('from_branch_id', $employee->branch_id);
        } elseif ($request->input('direccion') === 'recibidos') {
            $query->where('to_branch_id', $employee->branch_id);
        }

        $transfers = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('employee.pages.transfers.index', compact('transfers'));
    }

    /**
     * Formulario de nuevo traspaso: productos con stock en la sede de origen.
     */
    public function create()
    {
        $employee = auth()->guard('employee')->user();

        $branches = Branch::where('company_id', $employee->company_id)
            ->where('status', 1)
            ->where('id', '!=', $employee->branch_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $stocks = BranchStock::where('branch_id', $employee->branch_id)->where('stock_actual', '>', 0)->pluck('stock_actual', 'product_code');

        $products = Product::with('unit:id,abbreviation')
            ->where('company_id', $employee->company_id)
            ->where('status', 1)
            ->whereIn('code', $stocks->keys())
            ->orderBy('name')
            ->get(['code', 'name', 'unit_id'])
            ->map(fn ($p) => [
                'code'  => $p->code,
                'name'  => $p->name,
                'unit'  => $p->unit?->abbreviation,
                'stock' => (float) $stocks[$p->code],
            ])->values()->all();

        return view('employee.pages.transfers.form', compact('branches', 'products'));
    }

    /**
     * Registra el traspaso: descuenta de la sede de origen y suma en la de destino, en una transacción.
     */
    public function store(StockTransferRequest $request, StockService $stock)
    {
        $employee = auth()->guard('employee')->user();

        // Se procesan ordenados por código para tomar los bloqueos siempre en el mismo orden
        $items = collect($request->items)->sortBy('product_code')->values();
        $costs = Product::whereIn('code', $items->pluck('product_code'))->pluck('purchase_price', 'code');

        DB::beginTransaction();

        try {
            $transfer = StockTransfer::create([
                'company_id'     => $employee->company_id,
                'from_branch_id' => $employee->branch_id,
                'to_branch_id'   => $request->to_branch_id,
                'employee_id'    => $employee->id,
                'notes'          => $request->notes,
            ]);

            $batches = app(BatchService::class);

            foreach ($items as $item) {
                $quantity = round((float) $item['quantity'], 2);
                $cost     = (float) ($costs[$item['product_code']] ?? 0);

                // No se envían unidades vencidas
                if ($batches->sellable($employee->branch_id, $item['product_code']) < $quantity) {
                    throw new \RuntimeException('Hay unidades vencidas de ' . $item['product_code'] . ': solo se pueden enviar las vigentes.');
                }

                $transfer->items()->create(['product_code' => $item['product_code'], 'quantity' => $quantity]);

                $stock->move($employee->company_id, $employee->branch_id, $item['product_code'], -$quantity, 'transfer', $transfer->id, $cost, 'Salida hacia ' . $transfer->toBranch->name);
                $stock->move($employee->company_id, (int) $request->to_branch_id, $item['product_code'], $quantity, 'transfer', $transfer->id, $cost, 'Ingreso desde ' . $transfer->fromBranch->name);

                // Los lotes viajan con la mercadería: mismo número y vencimiento en la sede de destino
                foreach ($batches->consume($employee->branch_id, $item['product_code'], $quantity, 'transfer', $transfer->id) as $line) {
                    $batches->receive(
                        $employee->company_id, (int) $request->to_branch_id, $item['product_code'], $line['quantity'],
                        $line['batch']->batch, $line['batch']->expiration_date, $line['batch']->unit_cost, 'transfer', $transfer->id
                    );
                }
            }

            DB::commit();

            return redirect()->route('employee.transfers.show', $transfer)
                ->with('success', 'Traspaso registrado. El stock se actualizó en ambas sedes.');
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar traspaso: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error al registrar el traspaso. Inténtelo de nuevo.');
        }
    }

    /**
     * Detalle del traspaso.
     */
    public function show(StockTransfer $transfer)
    {
        $this->authorizeTransfer($transfer);

        $transfer->load(['fromBranch', 'toBranch', 'employee', 'items.product.unit']);

        return view('employee.pages.transfers.show', compact('transfer'));
    }

    /**
     * Anula el traspaso: devuelve la mercadería a la sede de origen.
     * Solo se puede si la sede de destino todavía tiene esas unidades.
     */
    public function void(Request $request, StockTransfer $transfer, StockService $stock)
    {
        $this->authorizeTransfer($transfer);

        $request->validate(['void_reason' => 'required|string|max:255'], [
            'void_reason.required' => 'Indica el motivo de la anulación.',
        ]);

        if (!$transfer->isActive()) {
            return back()->with('error', 'Este traspaso ya está anulado.');
        }

        $employee = auth()->guard('employee')->user();

        DB::beginTransaction();

        try {
            $transfer->load('items');
            $costs = Product::whereIn('code', $transfer->items->pluck('product_code'))->pluck('purchase_price', 'code');

            // Si el destino ya vendió parte de los lotes recibidos, no se puede devolver
            $batches = app(BatchService::class);
            if (!$batches->isIntact('transfer', $transfer->id, $transfer->to_branch_id)) {
                throw new \RuntimeException('La sede de destino ya vendió parte de esos lotes.');
            }

            foreach ($transfer->items->sortBy('product_code') as $item) {
                $cost = (float) ($costs[$item->product_code] ?? 0);
                $stock->move($employee->company_id, $transfer->to_branch_id, $item->product_code, -$item->quantity, 'transfer_void', $transfer->id, $cost, 'Anulación de traspaso');
                $stock->move($employee->company_id, $transfer->from_branch_id, $item->product_code, $item->quantity, 'transfer_void', $transfer->id, $cost, 'Anulación de traspaso');
            }

            // Los lotes vuelven a la sede de origen y salen del destino
            $batches->discardSource('transfer', $transfer->id, $transfer->to_branch_id);
            $batches->restore('transfer', $transfer->id);
            foreach ($transfer->items as $item) {
                $batches->reconcile($transfer->to_branch_id, $item->product_code);
            }

            $transfer->update(['status' => 0, 'voided_at' => now(), 'void_reason' => $request->void_reason]);

            DB::commit();

            \App\Models\AuditLog::record('transfer.void', 'Anuló el traspaso #' . $transfer->id, 'Traspaso #' . $transfer->id, ['motivo' => $request->void_reason]);

            return redirect()->route('employee.transfers.show', $transfer)->with('success', 'Traspaso anulado. La mercadería volvió a la sede de origen.');
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->with('error', 'No se puede anular: la sede de destino ya no tiene todas las unidades. ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al anular traspaso: ' . $e->getMessage());
            return back()->with('error', 'Error al anular el traspaso. Inténtelo de nuevo.');
        }
    }

    /** Solo las sedes involucradas (de la misma compañía) pueden ver el traspaso */
    private function authorizeTransfer(StockTransfer $transfer): void
    {
        $employee = auth()->guard('employee')->user();

        abort_if(
            $transfer->company_id !== $employee->company_id
                || !in_array($employee->branch_id, [$transfer->from_branch_id, $transfer->to_branch_id], true),
            403
        );
    }
}
