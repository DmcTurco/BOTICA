<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Laboratory;
use App\Models\Product;
use App\Models\StockBatch;
use App\Support\CsvExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $branchId = $employee->branch_id;

        // Filtros sobre el catálogo (sin cargar nada todavía)
        $base = Product::query()
            ->where('company_id', $employee->company_id)
            ->where('status', 1)
            ->search($request->buscar, ['name', 'code', 'barcode']);
        if ($request->filled('category')) {
            $base->where('category_id', $request->category);
        }
        if ($request->filled('laboratory')) {
            $base->where('laboratory_id', $request->laboratory);
        }
        if ($config['only_stock']) {
            $base->whereIn('code', BranchStock::select('product_code')->where('branch_id', $branchId)->where('stock_actual', '>', 0));
        }

        // Listado ordenado (por laboratorio si corresponde; los sin laboratorio al final) con el stock de la sede
        $list = (clone $base)
            ->with(['category:id,name', 'laboratory:id,name', 'unit:id,abbreviation'])
            ->select('products.*')
            ->addSelect(['stock_actual' => BranchStock::select('stock_actual')
                ->whereColumn('product_code', 'products.code')->where('branch_id', $branchId)->limit(1)])
            ->when($config['by_lab'], fn ($q) => $q
                ->orderByRaw('laboratory_id IS NULL')
                ->orderBy(Laboratory::select('name')->whereColumn('laboratories.id', 'products.laboratory_id')->limit(1)))
            ->orderBy('name')
            ->orderBy('code');

        $withValues = function (Product $p) {
            $p->stock_actual = (float) ($p->stock_actual ?? 0);
            $p->cost_value   = round($p->stock_actual * (float) $p->purchase_price, 2);
            $p->sale_value   = round($p->stock_actual * (float) $p->unit_sale_price, 2);

            return $p;
        };

        if ($request->query('export') === 'csv') {
            // El Excel trae todo, leído por bloques para no cargar el catálogo entero en memoria
            return CsvExport::download('inventario_' . $variant . '_' . now()->format('Ymd'),
                ['Laboratorio', 'Código', 'Producto', 'Categoría', 'Stock', 'Precio de compra', 'Precio de venta', 'Valor a costo', 'Valor a precio de venta'],
                $list->lazy(500)->map($withValues)->map(fn (Product $p) => [
                    $p->laboratory?->name ?? 'Sin laboratorio', $p->code, $p->name, $p->category?->name, (float) $p->stock_actual,
                    (float) $p->purchase_price, (float) $p->unit_sale_price, (float) $p->cost_value, (float) $p->sale_value,
                ]));
        }

        $products = $list->paginate(50)->withQueryString();
        $products->getCollection()->each($withValues);

        // Totales de todo el filtro (no solo de la página), calculados en la base de datos
        $totals = $this->totals($base, $branchId)->first();

        // Grupos de la página: por laboratorio (con el total del laboratorio completo) o un solo bloque
        $labTotals = $config['by_lab']
            ? $this->totals($base, $branchId)->addSelect('p.laboratory_id')->groupBy('p.laboratory_id')->get()
                ->keyBy(fn ($row) => (int) $row->laboratory_id)
            : collect();
        $groups = $config['by_lab']
            ? $products->getCollection()->groupBy(fn ($p) => $p->laboratory?->name ?? 'Sin laboratorio')
            : collect(['Todos los productos' => $products->getCollection()]);

        return view('employee.pages.reports.inventory', [
            'variant'      => $variant,
            'config'       => $config,
            'groups'       => $groups,
            'products'     => $products,
            'labTotals'    => $labTotals,
            'totalCost'    => (float) $totals->cost,
            'totalSale'    => (float) $totals->sale,
            'totalUnits'   => (float) $totals->units,
            'productCount' => (int) $totals->products,
            'categories'   => Category::where('company_id', $employee->company_id)->orderBy('name')->get(['id', 'name']),
            'laboratories' => Laboratory::where('company_id', $employee->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Consulta de totales (productos, unidades, valor a costo y a precio de venta) de los productos
     * del filtro, con el stock de la sede. Se suma en la base de datos para no cargar el catálogo.
     */
    private function totals(Builder $base, int $branchId): QueryBuilder
    {
        return DB::table('products as p')
            ->leftJoin('branch_stock as bs', fn ($join) => $join->on('bs.product_code', '=', 'p.code')->where('bs.branch_id', $branchId))
            ->whereIn('p.code', (clone $base)->select('code'))
            ->selectRaw('COUNT(*) AS products')
            ->selectRaw('COALESCE(SUM(bs.stock_actual), 0) AS units')
            ->selectRaw('COALESCE(SUM(ROUND(bs.stock_actual * p.purchase_price, 2)), 0) AS cost')
            ->selectRaw('COALESCE(SUM(ROUND(bs.stock_actual * p.unit_sale_price, 2)), 0) AS sale');
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
