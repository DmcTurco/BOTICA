<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\CashRegister;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Product;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    /**
     * Ventas activas (no anuladas) de la sede del empleado entre dos fechas.
     * Las notas de crédito dejan la venta en status 0, así que no suman.
     */
    private function salesQuery(Carbon $from, Carbon $to)
    {
        $employee = auth()->guard('employee')->user();

        return Order::where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->where('status', 1)
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString());
    }

    /** Lee un rango de fechas del request (por defecto el mes actual) */
    private function dateRange(Request $request): array
    {
        $from = $request->filled('fecha_desde') ? Carbon::parse($request->fecha_desde) : now()->startOfMonth();
        $to   = $request->filled('fecha_hasta') ? Carbon::parse($request->fecha_hasta) : now()->endOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->startOfDay()];
    }

    /**
     * Récord mensual: ventas día por día de un mes.
     */
    public function monthly(Request $request)
    {
        $month = $request->filled('mes') ? Carbon::createFromFormat('Y-m', $request->mes)->startOfMonth() : now()->startOfMonth();

        $orders = $this->salesQuery($month->copy(), $month->copy()->endOfMonth())
            ->get(['created_at', 'subtotal', 'igv', 'discount_amount', 'total']);

        // Se agrupa en PHP para no depender de funciones de fecha propias de cada motor de BD
        $byDay = $orders->groupBy(fn ($o) => $o->created_at->format('Y-m-d'));

        $days = collect(range(1, $month->daysInMonth))->map(function ($d) use ($month, $byDay) {
            $date = $month->copy()->day($d);
            $day  = $byDay->get($date->format('Y-m-d'), collect());

            return [
                'date'  => $date,
                'count' => $day->count(),
                'sub'   => (float) $day->sum('subtotal'),
                'igv'   => (float) $day->sum('igv'),
                'total' => (float) $day->sum('total'),
            ];
        });

        return view('employee.pages.reports.monthly', [
            'month'  => $month,
            'days'   => $days,
            'totals' => $this->totals($orders),
            'max'    => max(1, $days->max('total')),
        ]);
    }

    /**
     * Récord general: ventas mes por mes de un año.
     */
    public function general(Request $request)
    {
        $year = (int) ($request->input('anio') ?: now()->year);

        $orders = $this->salesQuery(Carbon::create($year, 1, 1), Carbon::create($year, 12, 31))
            ->get(['created_at', 'subtotal', 'igv', 'discount_amount', 'total']);

        $byMonth = $orders->groupBy(fn ($o) => (int) $o->created_at->format('n'));

        $months = collect(range(1, 12))->map(function ($m) use ($byMonth, $year) {
            $rows = $byMonth->get($m, collect());

            return [
                'date'  => Carbon::create($year, $m, 1),
                'count' => $rows->count(),
                'sub'   => (float) $rows->sum('subtotal'),
                'igv'   => (float) $rows->sum('igv'),
                'total' => (float) $rows->sum('total'),
            ];
        });

        return view('employee.pages.reports.general', [
            'year'   => $year,
            'months' => $months,
            'totals' => $this->totals($orders),
            'max'    => max(1, $months->max('total')),
            'years'  => range(now()->year, now()->year - 5),
        ]);
    }

    /**
     * Documentos emitidos en un mes (boletas, facturas y notas de venta) con su estado SUNAT.
     */
    public function documents(Request $request)
    {
        $month = $request->filled('mes') ? Carbon::createFromFormat('Y-m', $request->mes)->startOfMonth() : now()->startOfMonth();

        $query = $this->salesQuery($month->copy(), $month->copy()->endOfMonth());

        if ($request->filled('tipo')) {
            $query->where('voucher_type', $request->tipo);
        }
        if ($request->filled('sunat')) {
            $query->where('sunat_status', $request->sunat);
        }

        $totals = $this->totals((clone $query)->get(['voucher_type', 'subtotal', 'igv', 'discount_amount', 'total']));

        $orders = $query->orderBy('created_at')->paginate(25)->withQueryString();

        return view('employee.pages.reports.documents', compact('month', 'orders', 'totals'));
    }

    /**
     * Ventas por rango de fechas, con desglose por comprobante, forma de pago y día.
     */
    public function range(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $orders = $this->salesQuery($from->copy(), $to->copy())
            ->get(['created_at', 'voucher_type', 'payment_type', 'subtotal', 'igv', 'discount_amount', 'total']);

        $voucherLabels = [1 => 'Boletas', 2 => 'Facturas', 3 => 'Notas de venta'];

        return view('employee.pages.reports.range', [
            'from'      => $from,
            'to'        => $to,
            'totals'    => $this->totals($orders),
            'byVoucher' => collect($voucherLabels)->map(fn ($label, $type) => [
                'label' => $label,
                'count' => $orders->where('voucher_type', $type)->count(),
                'total' => (float) $orders->where('voucher_type', $type)->sum('total'),
            ]),
            'byPayment' => collect(CashRegister::PAYMENT_TYPE_LABELS)->map(fn ($label, $type) => [
                'label' => $label,
                'count' => $orders->where('payment_type', $type)->count(),
                'total' => (float) $orders->where('payment_type', $type)->sum('total'),
            ]),
            'byDay'     => $orders->groupBy(fn ($o) => $o->created_at->format('Y-m-d'))
                ->map(fn ($rows, $date) => ['date' => Carbon::parse($date), 'count' => $rows->count(), 'total' => (float) $rows->sum('total')])
                ->sortKeys()->values(),
        ]);
    }

    /**
     * Productos más vendidos en un rango de fechas.
     */
    public function bestSellers(Request $request)
    {
        $employee = auth()->guard('employee')->user();
        [$from, $to] = $this->dateRange($request);
        $limit = min(100, max(5, (int) $request->input('top', 20)));

        $rows = $this->soldByProduct($employee, $from, $to)
            ->orderByDesc('qty')->limit($limit)->get();

        $names = Product::withTrashed()->whereIn('code', $rows->pluck('product_code'))->pluck('name', 'code');

        if ($request->query('export') === 'csv') {
            return CsvExport::download('mas_vendidos_' . $from->format('Ymd') . '_' . $to->format('Ymd'), ['Código', 'Producto', 'Unidades vendidas', 'Venta neta'],
                $rows->map(fn ($r) => [$r->product_code, $names[$r->product_code] ?? '', (float) $r->qty, (float) $r->net]));
        }

        return view('employee.pages.reports.best-sellers', [
            'rows'  => $rows,
            'names' => $names,
            'from'  => $from,
            'to'    => $to,
            'limit' => $limit,
            'max'   => max(1, (float) $rows->max('qty')),
        ]);
    }

    /**
     * Productos con stock que no se vendieron en los últimos N días.
     */
    public function noRotation(Request $request)
    {
        $employee = auth()->guard('employee')->user();
        $days = min(365, max(7, (int) $request->input('dias', 60)));

        $soldCodes = DB::table('order_detail')
            ->join('orders', 'orders.id', '=', 'order_detail.order_id')
            ->where('orders.company_id', $employee->company_id)
            ->where('orders.branch_id', $employee->branch_id)
            ->where('orders.status', 1)
            ->whereDate('orders.created_at', '>=', now()->subDays($days)->toDateString())
            ->distinct()->pluck('order_detail.product_code');

        $stocks = BranchStock::where('branch_id', $employee->branch_id)->where('stock_actual', '>', 0)->pluck('stock_actual', 'product_code');

        $products = Product::with(['category:id,name', 'laboratory:id,name'])
            ->where('company_id', $employee->company_id)
            ->where('status', 1)
            ->whereIn('code', $stocks->keys())
            ->whereNotIn('code', $soldCodes)
            ->orderBy('name')
            ->get()
            ->each(function (Product $p) use ($stocks) {
                $p->stock_actual = (float) $stocks[$p->code];
                $p->stock_value  = round($p->stock_actual * (float) $p->purchase_price, 2);
            })
            ->sortByDesc('stock_value')->values();

        if ($request->query('export') === 'csv') {
            return CsvExport::download('sin_rotacion_' . $days . 'dias', ['Código', 'Producto', 'Categoría', 'Laboratorio', 'Stock', 'Precio de compra', 'Valor a costo'],
                $products->map(fn ($p) => [$p->code, $p->name, $p->category?->name, $p->laboratory?->name, (float) $p->stock_actual, (float) $p->purchase_price, (float) $p->stock_value]));
        }

        return view('employee.pages.reports.no-rotation', [
            'products'   => $products,
            'days'       => $days,
            'totalValue' => (float) $products->sum('stock_value'),
        ]);
    }

    /**
     * Utilidad por producto: ventas netas (sin IGV) menos el costo de lo vendido.
     * El costo usa el precio de compra actual del producto.
     */
    public function profit(Request $request)
    {
        $employee = auth()->guard('employee')->user();
        [$from, $to] = $this->dateRange($request);

        $rows = $this->soldByProduct($employee, $from, $to)->get();
        $products = Product::withTrashed()->whereIn('code', $rows->pluck('product_code'))->get(['code', 'name', 'purchase_price'])->keyBy('code');

        $rows = $rows->map(function ($r) use ($products) {
            $cost   = round((float) $r->qty * (float) ($products[$r->product_code]->purchase_price ?? 0), 2);
            $sales  = round((float) $r->net, 2);
            $profit = round($sales - $cost, 2);

            return (object) [
                'code'   => $r->product_code,
                'name'   => $products[$r->product_code]->name ?? $r->product_code,
                'qty'    => (float) $r->qty,
                'sales'  => $sales,
                'cost'   => $cost,
                'profit' => $profit,
                'margin' => $sales > 0 ? round($profit / $sales * 100, 1) : 0,
            ];
        })->sortByDesc('profit')->values();

        if ($request->query('export') === 'csv') {
            return CsvExport::download('utilidad_' . $from->format('Ymd') . '_' . $to->format('Ymd'), ['Código', 'Producto', 'Unidades', 'Venta neta', 'Costo', 'Utilidad', 'Margen %'],
                $rows->map(fn ($r) => [$r->code, $r->name, $r->qty, $r->sales, $r->cost, $r->profit, (float) $r->margin]));
        }

        return view('employee.pages.reports.profit', [
            'rows'   => $rows,
            'from'   => $from,
            'to'     => $to,
            'sales'  => (float) $rows->sum('sales'),
            'cost'   => (float) $rows->sum('cost'),
            'profit' => (float) $rows->sum('profit'),
        ]);
    }

    /**
     * Comisiones por vendedor: ventas netas de cada empleado × su porcentaje de comisión.
     */
    public function commissions(Request $request)
    {
        $employee = auth()->guard('employee')->user();
        [$from, $to] = $this->dateRange($request);

        $sales = $this->salesQuery($from->copy(), $to->copy())
            ->select('employee_id', DB::raw('COUNT(*) as documents'), DB::raw('SUM(subtotal) as net'), DB::raw('SUM(total) as gross'))
            ->groupBy('employee_id')->get()->keyBy('employee_id');

        $rows = Employee::where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->orderBy('name')->get(['id', 'name', 'commission_rate'])
            ->map(function (Employee $e) use ($sales) {
                $s = $sales->get($e->id);
                $net = (float) ($s->net ?? 0);

                return (object) [
                    'name'       => $e->name,
                    'rate'       => (float) $e->commission_rate,
                    'documents'  => (int) ($s->documents ?? 0),
                    'net'        => $net,
                    'gross'      => (float) ($s->gross ?? 0),
                    'commission' => round($net * (float) $e->commission_rate / 100, 2),
                ];
            })->filter(fn ($r) => $r->documents > 0 || $r->rate > 0)->values();

        if ($request->query('export') === 'csv') {
            return CsvExport::download('comisiones_' . $from->format('Ymd') . '_' . $to->format('Ymd'), ['Vendedor', 'Documentos', 'Venta neta', 'Comisión %', 'Comisión'],
                $rows->map(fn ($r) => [$r->name, $r->documents, (float) $r->net, (float) $r->rate, (float) $r->commission]));
        }

        return view('employee.pages.reports.commissions', [
            'rows' => $rows, 'from' => $from, 'to' => $to,
            'totalCommission' => (float) $rows->sum('commission'),
        ]);
    }

    /**
     * Pantalla donde se fija el porcentaje de comisión de cada empleado de la sede.
     */
    public function commissionSettings()
    {
        $employee = auth()->guard('employee')->user();

        $employees = Employee::where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->orderBy('name')->get(['id', 'name', 'email', 'commission_rate']);

        return view('employee.pages.reports.commission-settings', compact('employees'));
    }

    /**
     * Guarda los porcentajes de comisión.
     */
    public function updateCommissions(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $request->validate([
            'rates'   => 'required|array',
            'rates.*' => 'nullable|numeric|min:0|max:100',
        ], [
            'rates.*.max'     => 'La comisión no puede superar el 100%.',
            'rates.*.numeric' => 'La comisión debe ser un número.',
        ]);

        $ids = Employee::where('company_id', $employee->company_id)->where('branch_id', $employee->branch_id)->pluck('id');

        foreach ($request->rates as $id => $rate) {
            if ($ids->contains((int) $id)) {
                Employee::whereKey($id)->update(['commission_rate' => (float) ($rate ?: 0)]);
            }
        }

        \App\Models\AuditLog::record('commission.update', 'Actualizó los porcentajes de comisión', null, ['porcentajes' => $request->rates]);

        return redirect()->route('employee.commissions.settings')->with('success', 'Comisiones actualizadas.');
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** Cantidad vendida y venta neta por producto en un rango, de órdenes activas de la sede */
    private function soldByProduct(Employee $employee, Carbon $from, Carbon $to)
    {
        return DB::table('order_detail')
            ->join('orders', 'orders.id', '=', 'order_detail.order_id')
            ->where('orders.company_id', $employee->company_id)
            ->where('orders.branch_id', $employee->branch_id)
            ->where('orders.status', 1)
            ->whereDate('orders.created_at', '>=', $from->toDateString())
            ->whereDate('orders.created_at', '<=', $to->toDateString())
            ->select(
                'order_detail.product_code',
                DB::raw('SUM(order_detail.quantity) as qty'),
                DB::raw('SUM(order_detail.subtotal) as net')
            )
            ->groupBy('order_detail.product_code');
    }

    /** Totales de una colección de órdenes */
    private function totals($orders): array
    {
        return [
            'count'    => $orders->count(),
            'sub'      => (float) $orders->sum('subtotal'),
            'igv'      => (float) $orders->sum('igv'),
            'discount' => (float) $orders->sum('discount_amount'),
            'total'    => (float) $orders->sum('total'),
        ];
    }
}
