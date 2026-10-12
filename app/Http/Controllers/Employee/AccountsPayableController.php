<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountsPayableController extends Controller
{
    /**
     * Compras a crédito con saldo pendiente: lo que se debe a cada proveedor y qué está vencido.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $purchases = Purchase::with('supplierRecord:id,name')
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->where('payment_condition', 'credit')
            ->where('status', 1)
            ->whereColumn('paid_amount', '<', 'total')
            ->when($request->filled('proveedor'), fn ($q) => $q->where('supplier_id', $request->proveedor))
            ->when($request->input('estado') === 'vencidas', fn ($q) => $q->whereDate('due_date', '<', today()->toDateString()))
            ->orderBy('due_date')
            ->get();

        $bySupplier = $purchases->groupBy(fn ($p) => $p->supplierRecord?->name ?? ($p->supplier ?: 'Sin proveedor registrado'))
            ->map(fn ($rows) => ['count' => $rows->count(), 'debt' => (float) $rows->sum->balance]);

        if ($request->query('export') === 'csv') {
            return CsvExport::download('cuentas_por_pagar_' . now()->format('Ymd'), ['Compra', 'Fecha', 'Proveedor', 'Vence', 'Total', 'Pagado', 'Saldo'],
                $purchases->map(fn ($p) => [$p->document_number ?: '#' . $p->id, $p->purchased_at, $p->supplierRecord?->name ?? $p->supplier, $p->due_date, (float) $p->total, (float) $p->paid_amount, (float) $p->balance]));
        }

        return view('employee.pages.payables.index', [
            'purchases'  => $purchases,
            'bySupplier' => $bySupplier,
            'totalDebt'  => (float) $purchases->sum->balance,
            'overdue'    => (float) $purchases->filter->isOverdue()->sum->balance,
            'suppliers'  => Supplier::where('company_id', $employee->company_id)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Registra un pago al proveedor. No puede superar el saldo de la compra.
     */
    public function pay(Request $request, Purchase $purchase)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($purchase->company_id !== $employee->company_id || $purchase->branch_id !== $employee->branch_id, 403);

        $request->validate([
            'amount'    => 'required|numeric|min:0.01',
            'method'    => 'required|in:1,2,3,4',
            'paid_at'   => 'required|date|before_or_equal:today',
            'reference' => 'nullable|string|max:50',
        ], [
            'amount.required'          => 'Indica el monto a pagar.',
            'paid_at.before_or_equal'  => 'La fecha de pago no puede ser futura.',
        ]);

        $error = null;

        DB::transaction(function () use ($request, $purchase, $employee, &$error) {
            // Se bloquea la compra para que dos pagos simultáneos no superen el saldo
            $locked = Purchase::lockForUpdate()->find($purchase->id);
            $amount = round((float) $request->amount, 2);

            if ($locked->payment_condition !== 'credit' || (int) $locked->status === 0) {
                $error = 'Esta compra no tiene deuda pendiente.';
                return;
            }
            if ($amount > $locked->balance) {
                $error = 'El pago (S/ ' . number_format($amount, 2) . ') supera el saldo de la compra (S/ ' . number_format($locked->balance, 2) . ').';
                return;
            }

            SupplierPayment::create([
                'company_id'  => $employee->company_id,
                'purchase_id' => $locked->id,
                'amount'      => $amount,
                'method'      => $request->method,
                'reference'   => $request->reference,
                'paid_at'     => $request->paid_at,
                'employee_id' => $employee->id,
            ]);

            $locked->update(['paid_amount' => round((float) $locked->paid_amount + $amount, 2)]);
        });

        if (!$error) {
            \App\Models\AuditLog::record('payable.pay', 'Pagó S/ ' . number_format((float) $request->amount, 2) . ' al proveedor por la compra #' . $purchase->id, 'Compra #' . $purchase->id, ['forma' => $request->method]);
        }

        return $error
            ? back()->withInput()->with('error', $error)
            : back()->with('success', 'Pago registrado correctamente.');
    }
}
