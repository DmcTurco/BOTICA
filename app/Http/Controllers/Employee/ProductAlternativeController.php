<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\Product;
use App\Services\BatchService;
use Illuminate\Http\Request;

class ProductAlternativeController extends Controller
{
    /**
     * Productos con stock vendible que tienen el mismo principio activo (para ofrecer una alternativa
     * cuando el pedido no está disponible o el cliente quiere otra marca o un genérico).
     *
     * Se puede pedir por producto (?product_id=) o por texto libre (?q=, busca en principio activo y nombre).
     * Devuelve JSON ordenado de menor a mayor precio.
     */
    public function index(Request $request, BatchService $batches)
    {
        $employee = auth()->guard('employee')->user();

        $productId = (int) $request->query('product_id', 0);
        $term      = trim((string) $request->query('q', ''));
        $base      = $productId ? Product::where('company_id', $employee->company_id)->find($productId) : null;
        $query     = $base?->active_ingredient ?: $term;

        if ($productId && !$base) {
            return response()->json([]);
        }
        if (mb_strlen($query) < 3) {
            return response()->json([]);
        }

        $candidates = Product::with(['laboratory:id,name', 'unit:id,abbreviation'])
            ->where('company_id', $employee->company_id)
            ->where('status', 1)
            ->when($base, fn ($q) => $q->whereKeyNot($base->id)->search($base->active_ingredient, ['active_ingredient']))
            ->when(!$base, fn ($q) => $q->search($query, ['active_ingredient', 'name']))
            ->limit(40)->get();

        $stocks  = BranchStock::where('branch_id', $employee->branch_id)->whereIn('product_id', $candidates->pluck('id'))->pluck('stock_actual', 'product_id');
        $expired = $batches->expiredMap($employee->branch_id, $candidates->pluck('id')->all());

        return response()->json(
            $candidates
                ->map(fn (Product $p) => [
                    'id'         => $p->id,
                    'code'       => $p->code,
                    'name'       => $p->name,
                    'ingredient' => $p->active_ingredient,
                    'laboratory' => $p->laboratory?->name,
                    'price'      => (float) $p->unit_sale_price,
                    'stock'      => max(0, (float) ($stocks[$p->id] ?? 0) - ($expired[$p->id] ?? 0)),
                    'unit'       => $p->unit?->abbreviation,
                ])
                ->filter(fn ($p) => $p['stock'] > 0)
                ->sortBy('price')
                ->take(10)
                ->values()
        );
    }
}
