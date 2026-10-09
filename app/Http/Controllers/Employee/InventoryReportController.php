<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Laboratory;
use App\Models\Product;
use App\Models\StockBatch;
use App\Support\CsvExport;
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

        if ($request->query('export') === 'csv') {
            return CsvExport::download('inventario_' . $variant . '_' . now()->format('Ymd'),
                ['Laboratorio', 'Código', 'Producto', 'Categoría', 'Stock', 'Precio de compra', 'Precio de venta', 'Valor a costo', 'Valor a precio de venta'],
                $groups->flatMap(fn ($items, $lab) => $items->map(fn ($p) => [
                    $p->laboratory?->name ?? 'Sin laboratorio', $p->code, $p->name, $p->category?->name, (float) $p->stock_actual,
                    (float) $p->purchase_price, (float) $p->unit_sale_price, (float) $p->cost_value, (float) $p->sale_value,
                ])));
        }

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
     * Lotes con stock en la sede, ordenados por vencimiento: los vencidos y los que vencen en los
     * próximos N días (o todos con ?dias=todos). También muestra las unidades que no tienen lote asignado.
     */
    public function expiring(Request $request)
    {
        $employee = auth()->guard('employee')->user();
        $all      = $request->input('dias') === 'todos';
        $days     = $all ? null : min(365, max(7, (int) $request->input('dias', 60)));

        $batches = StockBatch::with('product:code,name')
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->where('quantity_remaining', '>', 0)
            ->when(!$all, fn ($q) => $q->whereNotNull('expiration_date')->whereDate('expiration_date', '<=', now()->addDays($days)->toDateString()))
            ->orderByRaw('expiration_date IS NULL')
            ->orderBy('expiration_date')
            ->get();

        $stocks = BranchStock::where('branch_id', $employee->branch_id)->pluck('stock_actual', 'product_code');

        $rows = $batches->map(fn (StockBatch $b) => [
            'id'      => $b->id,
            'code'    => $b->product_code,
            'name'    => $b->product?->name ?? $b->product_code,
            'batch'   => $b->batch,
            'origin'  => ['purchase' => 'Compra', 'production' => 'Preparado', 'transfer' => 'Traspaso', 'adjustment' => 'Ajuste'][$b->source_type] ?? $b->source_type,
            'qty'     => $b->quantity_remaining,
            'expires' => $b->expiration_date,
            'days'    => $b->daysToExpire(),
            'stock'   => (float) ($stocks[$b->product_code] ?? 0),
            'link'    => !$b->source_id ? null : match ($b->source_type) {
                'purchase'   => route('employee.purchases.show', $b->source_id),
                'production' => route('employee.productions.show', $b->source_id),
                'transfer'   => route('employee.transfers.show', $b->source_id),
                default      => null,
            },
        ]);

        if ($request->query('export') === 'csv') {
            return CsvExport::download('lotes_vencimientos_' . now()->format('Ymd'), ['Código', 'Producto', 'Lote', 'Origen', 'Unidades que quedan', 'Vence', 'Días para vencer'],
                $rows->map(fn ($r) => [$r['code'], $r['name'], $r['batch'], $r['origin'], (float) $r['qty'], $r['expires'], $r['days']]));
        }

        return view('employee.pages.reports.expiring', [
            'rows'    => $rows,
            'days'    => $days,
            'all'     => $all,
            'expired' => $rows->filter(fn ($r) => $r['days'] !== null && $r['days'] < 0)->count(),
        ]);
    }
}
