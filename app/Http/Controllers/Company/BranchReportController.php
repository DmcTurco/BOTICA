<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockBatch;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BranchReportController extends Controller
{
    /**
     * Resumen comparativo de todas las sedes de la empresa en un rango de fechas
     * (por defecto el mes): ventas, inventario, vencimientos, deudas y diferencias de caja.
     * También los productos más vendidos de toda la empresa. Descargable en CSV.
     */
    public function index(Request $request)
    {
        $company = auth()->guard('company')->user();

        $from = $request->filled('fecha_desde') ? Carbon::parse($request->fecha_desde) : now()->startOfMonth();
        $to   = $request->filled('fecha_hasta') ? Carbon::parse($request->fecha_hasta) : now()->endOfMonth();
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $branches = Branch::where('company_id', $company->id)->where('status', 1)->orderBy('name')->get(['id', 'name']);
        $ids      = $branches->pluck('id');

        // Ventas vigentes por sede en el rango
        $sales = Order::where('company_id', $company->id)->whereIn('branch_id', $ids)->where('status', 1)
            ->whereDate('created_at', '>=', $from->toDateString())->whereDate('created_at', '<=', $to->toDateString())
            ->selectRaw('branch_id, COUNT(*) as documents, SUM(subtotal) as net, SUM(total) as gross')
            ->groupBy('branch_id')->get()->keyBy('branch_id');

        // Inventario valorizado a costo y productos bajo el mínimo
        $costs  = Product::where('company_id', $company->id)->pluck('purchase_price', 'code');
        $stocks = BranchStock::whereIn('branch_id', $ids)->get(['branch_id', 'product_code', 'stock_actual', 'stock_minimum'])->groupBy('branch_id');

        // Unidades vencidas por sede, valoradas a su costo de lote
        $expired = StockBatch::where('company_id', $company->id)->whereIn('branch_id', $ids)->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiration_date')->whereDate('expiration_date', '<', today()->toDateString())
            ->selectRaw('branch_id, SUM(quantity_remaining * unit_cost) as value')->groupBy('branch_id')->pluck('value', 'branch_id');

        // Deudas por cobrar (fiado) y por pagar (compras a crédito)
        $receivable = Order::where('company_id', $company->id)->whereIn('branch_id', $ids)->where('status', 1)->where('credit_balance', '>', 0)
            ->selectRaw('branch_id, SUM(credit_balance) as debt')->groupBy('branch_id')->pluck('debt', 'branch_id');
        $payable = Purchase::where('company_id', $company->id)->whereIn('branch_id', $ids)->where('payment_condition', 'credit')->where('status', 1)
            ->selectRaw('branch_id, SUM(total - paid_amount) as debt')->groupBy('branch_id')->pluck('debt', 'branch_id');

        // Diferencias de caja (faltantes y sobrantes) cerradas en el rango
        $cashDiff = CashRegister::where('company_id', $company->id)->whereIn('branch_id', $ids)->where('status', 0)
            ->whereDate('register_date', '>=', $from->toDateString())->whereDate('register_date', '<=', $to->toDateString())
            ->selectRaw('branch_id, SUM(difference) as diff')->groupBy('branch_id')->pluck('diff', 'branch_id');

        $rows = $branches->map(function (Branch $b) use ($sales, $stocks, $costs, $expired, $receivable, $payable, $cashDiff) {
            $rowsStock = $stocks->get($b->id, collect());
            $s         = $sales->get($b->id);

            return (object) [
                'name'        => $b->name,
                'documents'   => (int) ($s->documents ?? 0),
                'net'         => (float) ($s->net ?? 0),
                'gross'       => (float) ($s->gross ?? 0),
                'inventory'   => round($rowsStock->sum(fn ($r) => (float) $r->stock_actual * (float) ($costs[$r->product_code] ?? 0)), 2),
                'low_stock'   => $rowsStock->filter(fn ($r) => $r->stock_minimum !== null && $r->stock_minimum > 0 && (float) $r->stock_actual <= $r->stock_minimum)->count(),
                'expired'     => round((float) ($expired[$b->id] ?? 0), 2),
                'receivable'  => round((float) ($receivable[$b->id] ?? 0), 2),
                'payable'     => round((float) ($payable[$b->id] ?? 0), 2),
                'cash_diff'   => round((float) ($cashDiff[$b->id] ?? 0), 2),
            ];
        });

        // Más vendidos de toda la empresa
        $top = DB::table('order_detail')->join('orders', 'orders.id', '=', 'order_detail.order_id')
            ->where('orders.company_id', $company->id)->where('orders.status', 1)
            ->whereDate('orders.created_at', '>=', $from->toDateString())->whereDate('orders.created_at', '<=', $to->toDateString())
            ->select('order_detail.product_code', DB::raw('MAX(order_detail.product_name) as name'), DB::raw('SUM(order_detail.quantity) as qty'), DB::raw('SUM(order_detail.subtotal) as net'))
            ->groupBy('order_detail.product_code')->orderByDesc('qty')->limit(10)->get();

        if ($request->query('export') === 'csv') {
            return CsvExport::download(
                'resumen_sedes_' . $from->format('Ymd') . '_' . $to->format('Ymd'),
                ['Sede', 'Documentos', 'Venta neta', 'Venta total', 'Inventario a costo', 'Productos bajo mínimo', 'Vencido a costo', 'Por cobrar', 'Por pagar', 'Diferencia de cajas'],
                $rows->map(fn ($r) => [$r->name, $r->documents, $r->net, $r->gross, $r->inventory, $r->low_stock, $r->expired, $r->receivable, $r->payable, $r->cash_diff])
            );
        }

        return view('company.pages.reports.branches', [
            'rows' => $rows, 'top' => $top, 'from' => $from, 'to' => $to,
            'totals' => [
                'documents' => $rows->sum('documents'), 'net' => $rows->sum('net'), 'gross' => $rows->sum('gross'),
                'inventory' => $rows->sum('inventory'), 'expired' => $rows->sum('expired'),
                'receivable' => $rows->sum('receivable'), 'payable' => $rows->sum('payable'), 'cash_diff' => $rows->sum('cash_diff'),
            ],
        ]);
    }
}
