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
     * Se puede pedir por producto (?code=) o por texto libre (?q=, busca en principio activo y nombre).
     * Devuelve JSON ordenado de menor a mayor precio.
     */
    public function index(Request $request, BatchService $batches)
    {
        $employee = auth()->guard('employee')->user();

        $code  = (string) $request->query('code', '');
        $term  = trim((string) $request->query('q', ''));
        $base  = $code !== '' ? Product::where('company_id', $employee->company_id)->find($code) : null;
        $query = $base?->active_ingredient ?: $term;

        if ($code !== '' && !$base) {
            return response()->json([]);
        }
        if (mb_strlen($query) < 3) {
            return response()->json([]);
        }

        $candidates = Product::with(['laboratory:id,name', 'unit:id,abbreviation'])
            ->where('company_id', $employee->company_id)
            ->where('status', 1)
            ->when($base, fn ($q) => $q->where('code', '!=', $base->code)->where('active_ingredient', 'like', '%' . $base->active_ingredient . '%'))
            ->when(!$base, fn ($q) => $q->where(fn ($w) => $w->where('active_ingredient', 'like', '%' . $query . '%')->orWhere('name', 'like', '%' . $query . '%')))
            ->limit(40)->get();

        $stocks  = BranchStock::where('branch_id', $employee->branch_id)->whereIn('product_code', $candidates->pluck('code'))->pluck('stock_actual', 'product_code');
        $expired = $batches->expiredMap($employee->branch_id, $candidates->pluck('code')->all());

        return response()->json(
            $candidates
                ->map(fn (Product $p) => [
                    'code'       => $p->code,
                    'name'       => $p->name,
                    'ingredient' => $p->active_ingredient,
                    'laboratory' => $p->laboratory?->name,
                    'price'      => (float) $p->unit_sale_price,
                    'stock'      => max(0, (float) ($stocks[$p->code] ?? 0) - ($expired[$p->code] ?? 0)),
                    'unit'       => $p->unit?->abbreviation,
                ])
                ->filter(fn ($p) => $p['stock'] > 0)
                ->sortBy('price')
                ->take(10)
                ->values()
        );
    }
}
