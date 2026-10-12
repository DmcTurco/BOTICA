<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ControlledBookController extends Controller
{
    /**
     * Libro de controlados: entradas, salidas y saldo de cada psicotrópico o estupefaciente
     * del mes, con los datos de la receta en cada despacho.
     */
    public function index(Request $request)
    {
        return view('employee.pages.controlled-book.index', $this->book($request) + [
            'controlled' => $this->controlledProducts(),
        ]);
    }

    /**
     * Hoja imprimible del libro (para archivar o presentar en una inspección).
     */
    public function print(Request $request)
    {
        return view('employee.pages.controlled-book.print', $this->book($request) + [
            'branch' => auth()->guard('employee')->user()->branch,
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** Productos controlados de la compañía */
    private function controlledProducts()
    {
        return Product::where('company_id', auth()->guard('employee')->user()->company_id)
            ->whereNotNull('controlled_type')
            ->orderBy('name')
            ->get(['code', 'name', 'controlled_type']);
    }

    /**
     * Arma el libro: por producto controlado, saldo inicial, movimientos del mes y saldo final.
     */
    private function book(Request $request): array
    {
        $employee = auth()->guard('employee')->user();
        $month    = $request->filled('mes') ? Carbon::createFromFormat('Y-m', $request->mes)->startOfMonth() : now()->startOfMonth();
        $from     = $month->copy();
        $to       = $month->copy()->endOfMonth();

        $products = $this->controlledProducts()
            ->when($request->filled('producto'), fn ($c) => $c->where('code', $request->producto));

        $movements = StockMovement::where('branch_id', $employee->branch_id)
            ->whereIn('product_code', $products->pluck('code'))
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->orderBy('created_at')->orderBy('id')
            ->get()
            ->groupBy('product_code');

        // Recetas de las ventas del período
        $orderIds      = $movements->flatten()->where('reference_type', 'order')->pluck('reference_id')->unique();
        $prescriptions = Prescription::whereIn('order_id', $orderIds)->get()->keyBy('order_id');

        $sections = $products->map(function (Product $product) use ($employee, $movements, $prescriptions, $from) {
            $rows  = $movements->get($product->code, collect());
            $first = $rows->first();

            // Saldo inicial: el saldo antes del primer movimiento del mes, o el último saldo anterior
            if ($first) {
                $signed  = $first->type === 'salida' ? -$first->quantity : $first->quantity;
                $opening = round($first->balance - $signed, 2);
            } else {
                $opening = (float) StockMovement::where('branch_id', $employee->branch_id)->where('product_code', $product->code)
                    ->whereDate('created_at', '<', $from->toDateString())->orderByDesc('id')->value('balance');
            }

            return [
                'product' => $product,
                'opening' => $opening,
                'closing' => $rows->isNotEmpty() ? (float) $rows->last()->balance : $opening,
                'rows'    => $rows->map(fn (StockMovement $m) => [
                    'date'    => $m->created_at,
                    'label'   => $m->referencia_label,
                    'in'      => $m->type === 'entrada' ? $m->quantity : ($m->type === 'ajuste' && $m->quantity > 0 ? $m->quantity : 0),
                    'out'     => $m->type === 'salida' ? $m->quantity : ($m->type === 'ajuste' && $m->quantity < 0 ? abs($m->quantity) : 0),
                    'balance' => $m->balance,
                    'rx'      => $m->reference_type === 'order' ? $prescriptions->get($m->reference_id) : null,
                ]),
            ];
        })->values();

        return ['month' => $month, 'sections' => $sections, 'selected' => $request->input('producto')];
    }
}
