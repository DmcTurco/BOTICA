<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\CashRegister;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalController extends Controller
{
    /**
     * Lista todas las cajas históricas pendientes de la sede del branch_admin.
     */
    public function index()
    {
        $admin = auth()->guard('employee')->user();

        $pending = CashRegister::with(['employee', 'orders.items'])
            ->where('company_id', $admin->company_id)
            ->where('branch_id', $admin->branch_id)
            ->where('approval_status', CashRegister::APPROVAL_PENDING)
            ->where('status', 0) // solo cajas ya cerradas por el empleado
            ->orderBy('register_date', 'desc')
            ->get();

        $recentHistory = CashRegister::with(['employee', 'approvedBy'])
            ->where('company_id', $admin->company_id)
            ->where('branch_id', $admin->branch_id)
            ->whereIn('approval_status', [CashRegister::APPROVAL_APPROVED, CashRegister::APPROVAL_REJECTED])
            ->orderBy('approved_at', 'desc')
            ->limit(20)
            ->get();

        return view('employee.pages.approvals.index', compact('pending', 'recentHistory'));
    }

    /**
     * Aprueba una caja histórica pendiente.
     * Las órdenes ya descontaron stock al registrarse — no hay cambios adicionales.
     */
    public function approveCashRegister(CashRegister $cashRegister)
    {
        $admin = auth()->guard('employee')->user();

        abort_if(
            $cashRegister->company_id !== $admin->company_id ||
            $cashRegister->branch_id  !== $admin->branch_id,
            403
        );
        abort_if($cashRegister->approval_status !== CashRegister::APPROVAL_PENDING, 422);

        $cashRegister->update([
            'approval_status' => CashRegister::APPROVAL_APPROVED,
            'approved_by'     => $admin->id,
            'approved_at'     => now(),
            'rejection_reason'=> null,
        ]);

        $dateLabel = $cashRegister->register_date->format('d/m/Y');
        return back()->with('success', "Caja del {$dateLabel} ({$cashRegister->employee->name}) aprobada correctamente.");
    }

    /**
     * Rechaza una caja histórica pendiente y revierte el stock de sus órdenes.
     */
    public function rejectCashRegister(Request $request, CashRegister $cashRegister)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'Indica el motivo del rechazo.',
        ]);

        $admin = auth()->guard('employee')->user();

        abort_if(
            $cashRegister->company_id !== $admin->company_id ||
            $cashRegister->branch_id  !== $admin->branch_id,
            403
        );
        abort_if($cashRegister->approval_status !== CashRegister::APPROVAL_PENDING, 422);

        DB::beginTransaction();
        try {
            // Revertir stock de cada ítem de cada orden de esta caja
            $orders = $cashRegister->orders()->with('items')->where('status', 1)->get();

            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    $branchStock = BranchStock::where('branch_id', $cashRegister->branch_id)
                        ->where('product_code', $item->product_code)
                        ->lockForUpdate()
                        ->first();

                    if ($branchStock) {
                        $nuevoStock = $branchStock->stock_actual + $item->quantity;
                        $branchStock->update(['stock_actual' => $nuevoStock]);

                        // Registrar reversión en el kardex
                        StockMovement::create([
                            'company_id'     => $cashRegister->company_id,
                            'branch_id'      => $cashRegister->branch_id,
                            'product_code'   => $item->product_code,
                            'type'           => 'entrada',
                            'reference_type' => 'order_reversal',
                            'reference_id'   => $order->id,
                            'quantity'       => (int) $item->quantity,
                            'unit_cost'      => $item->unit_price,
                            'balance'        => (int) $nuevoStock,
                        ]);
                    }
                }

                // Anular la orden
                $order->update(['status' => 0]);
            }

            // Marcar la caja como rechazada
            $cashRegister->update([
                'approval_status'  => CashRegister::APPROVAL_REJECTED,
                'approved_by'      => $admin->id,
                'approved_at'      => now(),
                'rejection_reason' => $request->rejection_reason,
            ]);

            DB::commit();

            $dateLabel = $cashRegister->register_date->format('d/m/Y');
            return back()->with('success', "Caja del {$dateLabel} rechazada. Stock revertido correctamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al rechazar caja histórica: ' . $e->getMessage());
            return back()->with('error', 'Error al rechazar la caja. Intenta de nuevo.');
        }
    }
}
