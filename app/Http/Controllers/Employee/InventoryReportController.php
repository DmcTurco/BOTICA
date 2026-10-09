<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Laboratory;
use App\Models\Product;
use App\Models\Production;
use App\Models\PurchaseDetail;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    /**
     * Variantes del reporte de inventario: cada una tiene su propio privilegio y ruta.
     *   only_stock → solo productos con stock · by_lab → agrupado por laboratorio
     */
    const VARIANTS = [
        'total'            => ['title' => 'Inventario Total',                  'only_stock' => false, 'by_lab' => false],
        'stock'            => ['title' => 'Inventario con Stock',              'only_stock' => true,  'by_lab' => false],
        'laboratory'       => ['title' => 'Inventario por Laboratorio',        'only_stock' => false, 'by_lab' => true],
        'laboratory-stock' => ['title' => 'Inventario por Laboratorio con Stock', 'only_stock' => true, 'by_lab' => true],
        'valued'           => ['title' => 'Inventario Valorizado',             'only_stock' => true,  'by_lab' => false],
    ];

    /**
     * Inventario de la sede con su valor a costo y a precio de venta.
     */
    public function index(Request $request, string $variant)
    {
        abort_unless(isset(self::VARIANTS[$variant]), 404);

        $employee = auth()->guard('employee')->user();
        $config   = self::VARIANTS[$variant];

        $stocks = BranchStock::where('branch_id', $employee->branch_id)->pluck('stock_actual', 'product_code');

        $query = Product::with(['category:id,name', 'laboratory:id,name', 'unit:id,abbreviation'])
            ->where('company_id', $employee->company_id)
            ->where('status', 1);

        if ($request->filled('buscar')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%' . $request->buscar . '%')->orWhere('code', 'like', '%' . $request->buscar . '%'));
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('laboratory')) {
            $query->where('laboratory_id', $request->laboratory);
        }

        $products = $query->orderBy('name')->get()->each(function (Product $p) use ($stocks) {
            $p->stock_actual = (float) ($stocks[$p->code] ?? 0);
            $p->cost_value   = round($p->stock_actual * (float) $p->purchase_price, 2);
            $p->sale_value   = round($p->stock_actual * (float) $p->unit_sale_price, 2);
        });

        if ($config['only_stock']) {
            $products = $products->where('stock_actual', '>', 0);
        }

        // Grupos: por laboratorio o un solo bloque
        $groups = $config['by_lab']
            ? $products->groupBy(fn ($p) => $p->laboratory?->name ?? 'Sin laboratorio')->sortKeys()
            : collect(['Todos los productos' => $products]);

        return view('employee.pages.reports.inventory', [
            'variant'      => $variant,
            'config'       => $config,
            'groups'       => $groups,
            'totalCost'    => (float) $products->sum('cost_value'),
            'totalSale'    => (float) $products->sum('sale_value'),
            'totalUnits'   => (float) $products->sum('stock_actual'),
            'productCount' => $products->count(),
            'categories'   => Category::where('company_id', $employee->company_id)->orderBy('name')->get(['id', 'name']),
            'laboratories' => Laboratory::where('company_id', $employee->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Lotes por vencer o vencidos: compras con fecha de vencimiento y preparados magistrales,
     * solo de productos que aún tienen stock en la sede.
     */
    public function expiring(Request $request)
    {
        $employee = auth()->guard('employee')->user();
        $days     = min(365, max(7, (int) $request->input('dias', 60)));
        $limit    = now()->addDays($days)->toDateString();

        $stocks = BranchStock::where('branch_id', $employee->branch_id)->where('stock_actual', '>', 0)->pluck('stock_actual', 'product_code');

        $purchased = PurchaseDetail::with(['product:code,name', 'purchase:id,purchased_at'])
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', $limit)
            ->whereIn('product_code', $stocks->keys())
            ->whereHas('purchase', fn ($q) => $q->where('company_id', $employee->company_id)
                ->where('branch_id', $employee->branch_id)->where('status', 1))
            ->get()
            ->map(fn (PurchaseDetail $d) => [
                'origin'  => 'Compra',
                'code'    => $d->product_code,
                'name'    => $d->product?->name ?? $d->product_code,
                'batch'   => $d->batch,
                'qty'     => (float) $d->quantity,
                'expires' => $d->expiration_date,
                'link'    => route('employee.purchases.show', $d->purchase_id),
            ]);

        $produced = Production::with('formula:id,name')
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->where('status', 1)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', $limit)
            // Un preparado vendido en el POS solo cuenta si su producto aún tiene stock
            ->where(fn ($q) => $q->whereNull('product_code')->orWhereIn('product_code', $stocks->keys()))
            ->get()
            ->map(fn (Production $p) => [
                'origin'  => 'Preparado',
                'code'    => $p->product_code ?? $p->formula?->name,
                'name'    => $p->formula?->name ?? '—',
                'batch'   => $p->batch,
                'qty'     => (float) $p->quantity_produced,
                'expires' => $p->expiration_date,
                'link'    => route('employee.productions.show', $p->id),
            ]);

        $rows = $purchased->concat($produced)->sortBy(fn ($r) => $r['expires']->timestamp)->values()
            ->map(function ($r) use ($stocks) {
                $r['days']  = (int) now()->startOfDay()->diffInDays($r['expires']->copy()->startOfDay(), false);
                $r['stock'] = $stocks[$r['code']] ?? null;
                return $r;
            });

        return view('employee.pages.reports.expiring', [
            'rows'    => $rows,
            'days'    => $days,
            'expired' => $rows->where('days', '<', 0)->count(),
        ]);
    }
}
