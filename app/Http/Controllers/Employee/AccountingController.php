<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\DocumentType;
use App\Models\Order;
use App\Models\Purchase;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AccountingController extends Controller
{
    /** Código SUNAT (catálogo 01) por tipo de comprobante de venta; la nota de venta no es tributaria */
    private const SALE_DOC_CODES = [1 => '03', 2 => '01', 3 => 'NV'];

    /**
     * Registro de ventas del mes: boletas, facturas, notas de venta y notas de crédito, con anulados.
     * Las notas de crédito restan (importes en negativo) y los comprobantes anulados salen en 0.
     * Se descarga en CSV (Excel) con ?export=csv.
     */
    public function sales(Request $request)
    {
        $employee = auth()->guard('employee')->user();
        $month    = $request->filled('mes') ? Carbon::createFromFormat('Y-m', $request->mes)->startOfMonth() : now()->startOfMonth();
        $from     = $month->copy()->toDateString();
        $to       = $month->copy()->endOfMonth()->toDateString();

        $docTypes = DocumentType::pluck('code', 'id');

        $orders = Order::where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->orderBy('created_at')->orderBy('id')->get();

        $notes = CreditNote::with('order:id,voucher_number,customer_name,customer_document,document_type_id')
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->orderBy('created_at')->get();

        $rows = collect();

        foreach ($orders as $o) {
            $void = (int) $o->status === 0;
            $rows->push($this->row(
                $o->created_at, self::SALE_DOC_CODES[$o->voucher_type] ?? '—', $o->voucher_number,
                $docTypes[$o->document_type_id] ?? '', $o->customer_document, $o->customer_name,
                $void ? 0 : $o->taxable_amount, $void ? 0 : $o->exonerated_amount, $void ? 0 : $o->unaffected_amount,
                $void ? 0 : $o->igv, $void ? 0 : $o->total, $void ? 'Anulado' : 'Vigente',
                (int) $o->voucher_type === 3
            ));
        }

        foreach ($notes as $n) {
            $o = $n->order;
            $rows->push($this->row(
                $n->created_at, '07', $n->voucher_number, $docTypes[$o?->document_type_id] ?? '', $o?->customer_document, $o?->customer_name,
                -$n->taxable_amount, -$n->exonerated_amount, -$n->unaffected_amount, -$n->igv, -$n->total, 'Nota de crédito de ' . $o?->voucher_number, false
            ));
        }

        $rows = $rows->sortBy('date')->values();
        $tax  = $rows->where('internal', false);             // las notas de venta no entran a los totales tributarios

        if ($request->query('export') === 'csv') {
            return CsvExport::download(
                'registro_ventas_' . $month->format('Y-m'),
                ['Fecha', 'Tipo comprobante', 'Serie', 'Número', 'Tipo doc. cliente', 'Documento', 'Cliente', 'Base gravada', 'Exonerada', 'Inafecta', 'IGV', 'Total', 'Estado'],
                $rows->map(fn ($r) => [$r['date'], $r['type'], $r['series'], $r['number'], $r['doc_type'], $r['document'], $r['customer'],
                    (float) $r['taxable'], (float) $r['exonerated'], (float) $r['unaffected'], (float) $r['igv'], (float) $r['total'], $r['status']])
            );
        }

        return view('employee.pages.accounting.sales', [
            'month'  => $month,
            'rows'   => $rows,
            'totals' => [
                'taxable'    => (float) $tax->sum('taxable'),
                'exonerated' => (float) $tax->sum('exonerated'),
                'unaffected' => (float) $tax->sum('unaffected'),
                'igv'        => (float) $tax->sum('igv'),
                'total'      => (float) $tax->sum('total'),
            ],
        ]);
    }

    /**
     * Registro de compras del mes con proveedor, IGV y condición de pago. Descargable en CSV.
     */
    public function purchases(Request $request)
    {
        $employee = auth()->guard('employee')->user();
        $month    = $request->filled('mes') ? Carbon::createFromFormat('Y-m', $request->mes)->startOfMonth() : now()->startOfMonth();

        $purchases = Purchase::with('supplierRecord:id,name,ruc')
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->whereDate('purchased_at', '>=', $month->toDateString())
            ->whereDate('purchased_at', '<=', $month->copy()->endOfMonth()->toDateString())
            ->orderBy('purchased_at')->orderBy('id')->get();

        $valid = $purchases->where('status', 1);

        if ($request->query('export') === 'csv') {
            return CsvExport::download(
                'registro_compras_' . $month->format('Y-m'),
                ['Fecha', 'Tipo documento', 'N° documento', 'RUC proveedor', 'Proveedor', 'Subtotal', 'IGV', 'Total', 'Condición', 'Estado'],
                $purchases->map(fn (Purchase $p) => [
                    $p->purchased_at, $p->document_type_label, $p->document_number, $p->supplierRecord?->ruc, $p->supplierRecord?->name ?? $p->supplier,
                    (float) ((int) $p->status ? $p->subtotal : 0), (float) ((int) $p->status ? $p->tax : 0), (float) ((int) $p->status ? $p->total : 0),
                    $p->payment_condition === 'credit' ? 'Crédito' : 'Contado', (int) $p->status ? 'Vigente' : 'Anulada',
                ])
            );
        }

        return view('employee.pages.accounting.purchases', [
            'month'     => $month,
            'purchases' => $purchases,
            'totals'    => ['subtotal' => (float) $valid->sum('subtotal'), 'tax' => (float) $valid->sum('tax'), 'total' => (float) $valid->sum('total')],
        ]);
    }

    /** Fila normalizada del registro de ventas */
    private function row($date, string $type, ?string $voucher, ?string $docType, ?string $document, ?string $customer, $taxable, $exonerated, $unaffected, $igv, $total, string $status, bool $internal): array
    {
        [$series, $number] = array_pad(explode('-', (string) $voucher, 2), 2, '');

        return compact('date', 'type', 'series', 'number', 'document', 'customer', 'taxable', 'exonerated', 'unaffected', 'igv', 'total', 'status', 'internal') + ['doc_type' => $docType];
    }
}
