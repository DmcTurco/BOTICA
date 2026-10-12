<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\Order;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountsReceivableController extends Controller
{
    /**
     * Clientes que deben por ventas a crédito (fiado), del que más debe al que menos,
     * con lo vencido y la fecha más antigua.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $orders = $this->openOrders($employee->company_id)
            ->with('client:id,code,name,phone,document_number')
            ->get();

        $clients = $orders->groupBy('client_id')->map(function ($rows) {
            $client = $rows->first()->client;

            return (object) [
                'client'   => $client,
                'orders'   => $rows->count(),
                'debt'     => (float) $rows->sum('credit_balance'),
                'overdue'  => (float) $rows->filter(fn ($o) => $o->credit_due_date && $o->credit_due_date->lt(today()))->sum('credit_balance'),
                'oldest'   => $rows->min('credit_due_date'),
            ];
        })
            ->when($request->filled('buscar'), fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower($r->client?->name . ' ' . $r->client?->document_number), mb_strtolower($request->buscar))))
            ->when($request->input('estado') === 'vencidos', fn ($c) => $c->filter(fn ($r) => $r->overdue > 0))
            ->sortByDesc('debt')->values();

        if ($request->query('export') === 'csv') {
            return CsvExport::download('cuentas_por_cobrar_' . now()->format('Ymd'), ['Cliente', 'Documento', 'Teléfono', 'Ventas', 'Primer vencimiento', 'Vencido', 'Debe'],
                $clients->map(fn ($r) => [$r->client?->name, $r->client?->document_number, $r->client?->phone, $r->orders, $r->oldest, (float) $r->overdue, (float) $r->debt]));
        }

        return view('employee.pages.receivables.index', [
            'clients'   => $clients,
            'totalDebt' => (float) $orders->sum('credit_balance'),
            'overdue'   => (float) $orders->filter(fn ($o) => $o->credit_due_date && $o->credit_due_date->lt(today()))->sum('credit_balance'),
        ]);
    }

    /**
     * Estado de cuenta de un cliente: ventas a crédito con saldo, historial de abonos y formulario de cobro.
     */
    public function show(Client $client)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($client->company_id !== $employee->company_id, 403);

        $orders = $this->openOrders($employee->company_id)->where('client_id', $client->id)->get();

        $payments = CreditPayment::with(['order:id,voucher_number', 'employee:id,name'])
            ->where('client_id', $client->id)->orderByDesc('id')->limit(30)->get();

        return view('employee.pages.receivables.show', [
            'client'   => $client,
            'orders'   => $orders,
            'payments' => $payments,
            'debt'     => (float) $orders->sum('credit_balance'),
            'hasCaja'  => CashRegister::currentOpen($employee->id)->exists(),
        ]);
    }

    /**
     * Registra un abono y lo reparte entre las ventas más antiguas primero.
     * Si es en efectivo queda en la caja abierta del cajero (suma al efectivo esperado).
     */
    public function pay(Request $request, Client $client)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($client->company_id !== $employee->company_id, 403);

        $request->validate([
            'amount'    => 'required|numeric|min:0.01',
            'method'    => 'required|in:1,2,3,4',
            'paid_at'   => 'required|date|before_or_equal:today',
            'reference' => 'nullable|string|max:50',
        ], [
            'amount.required'         => 'Indica el monto del abono.',
            'paid_at.before_or_equal' => 'La fecha del abono no puede ser futura.',
        ]);

        $error = null;

        DB::transaction(function () use ($request, $client, $employee, &$error) {
            $caja = CashRegister::currentOpen($employee->id)->latest('opened_at')->first();

            if ((int) $request->method === CashRegister::PAYMENT_CASH && !$caja) {
                $error = 'Abre tu caja para recibir abonos en efectivo.';
                return;
            }

            // Se bloquean las ventas con deuda para que dos cobros simultáneos no se pisen
            $orders = $this->openOrders($employee->company_id)->where('client_id', $client->id)->lockForUpdate()->get();
            $amount = round((float) $request->amount, 2);

            if ($amount > round((float) $orders->sum('credit_balance'), 2)) {
                $error = 'El abono (S/ ' . number_format($amount, 2) . ') supera la deuda del cliente (S/ ' . number_format($orders->sum('credit_balance'), 2) . ').';
                return;
            }

            $batch = (string) Str::uuid();

            foreach ($orders as $order) {
                if ($amount <= 0) {
                    break;
                }

                $portion = round(min($amount, (float) $order->credit_balance), 2);

                CreditPayment::create([
                    'company_id'       => $employee->company_id,
                    'branch_id'        => $order->branch_id,
                    'order_id'         => $order->id,
                    'client_id'        => $client->id,
                    'cash_register_id' => (int) $request->method === CashRegister::PAYMENT_CASH ? $caja->id : null,
                    'employee_id'      => $employee->id,
                    'amount'           => $portion,
                    'method'           => $request->method,
                    'reference'        => $request->reference,
                    'paid_at'          => $request->paid_at,
                    'batch'            => $batch,
                ]);

                $order->update(['credit_balance' => round((float) $order->credit_balance - $portion, 2)]);
                $amount = round($amount - $portion, 2);
            }
        });

        if (!$error) {
            \App\Models\AuditLog::record('receivable.pay', 'Cobró S/ ' . number_format((float) $request->amount, 2) . ' a ' . $client->name, $client->code, ['forma' => $request->method]);
        }

        return $error
            ? back()->withInput()->with('error', $error)
            : back()->with('success', 'Abono registrado correctamente.');
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** Ventas a crédito vigentes con saldo pendiente, las más antiguas (por vencimiento) primero */
    private function openOrders(int $companyId)
    {
        return Order::where('company_id', $companyId)
            ->where('payment_type', CashRegister::PAYMENT_CREDIT)
            ->where('status', 1)
            ->where('credit_balance', '>', 0)
            ->orderBy('credit_due_date')
            ->orderBy('id');
    }
}
