<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\DocumentSeries;
use App\Models\DocumentType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\Sunat\TaxCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Muestra el historial de órdenes con filtros.
     */
    public function historial(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $query = Order::where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('buscar')) {
            $search = $request->buscar;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_document', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        if ($request->filled('tipo_comprobante')) {
            $query->where('voucher_type', $request->tipo_comprobante);
        }

        if ($request->filled('tipo_pago')) {
            $query->where('payment_type', $request->tipo_pago);
        }

        // Rango de fechas: por defecto del 1er al último día del mes actual
        $fechaDesde = $request->input('fecha_desde') ?: now()->startOfMonth()->toDateString();
        $fechaHasta = $request->input('fecha_hasta') ?: now()->endOfMonth()->toDateString();

        $query->whereDate('created_at', '>=', $fechaDesde)
              ->whereDate('created_at', '<=', $fechaHasta);

        $orders = $query->paginate(15)->withQueryString();

        return view('employee.pages.orders.historial', compact('orders', 'fechaDesde', 'fechaHasta'));
    }

    /**
     * Calcula el IGV y los totales de una venta con la afectación de cada producto
     * leída de la base de datos (nunca del navegador).
     * Devuelve ['tax' => cálculo, 'units' => código SUNAT de unidad por producto]
     * o ['error' => mensaje] si algún producto no es de la compañía o los totales no coinciden.
     */
    private function pricing(array $items, int $companyId, mixed $clientTotal): array
    {
        $codes    = array_unique(array_column($items, 'code'));
        $products = Product::with('unit:id,sunat_code')
            ->where('company_id', $companyId)
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');

        if ($products->count() !== count($codes)) {
            return ['error' => 'Hay productos que no pertenecen a tu compañía.'];
        }

        $tax = app(TaxCalculator::class)->calculate(
            $items,
            $products->map(fn ($p) => $p->igv_affectation)->all()
        );

        // El total que vio el cajero debe coincidir con el del servidor (tolerancia por redondeo)
        if ($clientTotal !== null && abs($tax['total'] - (float) $clientTotal) > 0.01 * (count($items) + 1)) {
            return ['error' => 'Los totales no coinciden con los precios actuales. Actualiza la página e inténtalo de nuevo.'];
        }

        return [
            'tax'   => $tax,
            'units' => $products->map(fn ($p) => $p->unit?->sunat_code ?? 'NIU')->all(),
        ];
    }

    /**
     * Bloquea (FOR UPDATE) las filas de stock de la sede para los códigos dados.
     * Se bloquean todas juntas y ordenadas por código para que dos ventas con los
     * mismos productos en distinto orden no se esperen entre sí (deadlock).
     * Devuelve las filas indexadas por código de producto.
     */
    private function lockStock(int $branchId, array $codes): \Illuminate\Support\Collection
    {
        return BranchStock::where('branch_id', $branchId)
            ->whereIn('product_code', array_unique($codes))
            ->orderBy('product_code')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_code');
    }

    /**
     * Verifica que el stock alcance para los ítems, sumando las cantidades cuando
     * un mismo producto aparece en varias líneas.
     * Devuelve el nombre del primer producto sin stock suficiente, o null si todo alcanza.
     */
    private function insufficientStock(array $items, \Illuminate\Support\Collection $stocks): ?string
    {
        $required = [];
        $names    = [];

        foreach ($items as $item) {
            $code            = $item['code'];
            $required[$code] = ($required[$code] ?? 0) + (float) $item['qty'];
            $names[$code]  ??= $item['name'] ?? $code;
        }

        foreach ($required as $code => $qty) {
            $stock = $stocks->get($code);

            if (!$stock || (float) $stock->stock_actual < $qty) {
                return $names[$code];
            }
        }

        return null;
    }

    /**
     * Devuelve el detalle de una orden en JSON (para modal).
     */
    public function detalle(Order $order)
    {
        $employee = auth()->guard('employee')->user();
        abort_if($order->company_id !== $employee->company_id, 403);

        $order->load('items');

        return response()->json([
            'id'                => $order->id,
            'created_at'        => $order->created_at->format('d/m/Y H:i'),
            'customer_name'     => $order->customer_name,
            'document_type_id'  => $order->document_type_id,
            'document_type'     => $order->documentType?->name,
            'customer_document' => $order->customer_document,
            'voucher_type'      => $order->voucher_type,
            'voucher_number'    => $order->voucher_number,
            'payment_type'      => $order->payment_type,
            'operation_number'  => $order->operation_number,
            'subtotal'          => $order->subtotal,
            'igv'               => $order->igv,
            'total'             => $order->total,
            'status'            => $order->status,
            'items'             => $order->items->map(fn($item) => [
                'product_code' => $item->product_code,
                'product_name' => $item->product_name,
                'unit_price'   => $item->unit_price,
                'quantity'     => $item->quantity,
                'subtotal'     => $item->subtotal,
            ]),
        ]);
    }

    /**
     * Muestra la pantalla de punto de venta.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        // Caja histórica sobre la que se está registrando (la deja el middleware cash.open)
        $historicalCaja = $request->attributes->get('cash_register');

        $branchId = $employee->branch_id;

        // Solo mostrar productos con stock disponible en la sede del empleado
        $products = Product::with([
                'laboratory',
                'presentations.unit',
                'branchStocks' => fn ($q) => $q->where('branch_id', $branchId),
            ])
            ->where('company_id', $employee->company_id)
            ->where('status', 1)
            ->whereHas('branchStocks', fn ($q) => $q->where('branch_id', $branchId)->where('stock_actual', '>', 0))
            ->orderBy('name')
            ->get();

        $documentTypes = DocumentType::activos()->get();

        // Configuración de impresión de la sede (para el modal post-venta)
        $branch      = $employee->branch()->with('config')->first();
        $printConfig = array_merge([
            'default_template' => 'ticket_80mm',
            'auto_print'       => false,
        ], $branch->getSettingGroup('printing'));

        return view('employee.pages.orders.index', compact('products', 'documentTypes', 'printConfig', 'historicalCaja'));
    }

    /**
     * Consulta nombre de cliente por DNI o RUC en APIs externas.
     */
    public function consultarDocumento(Request $request)
    {
        $numero = preg_replace('/\D/', '', $request->query('numero', ''));
        $token  = config('services.apisperu.token');

        if (empty($token)) {
            return response()->json(['success' => false, 'message' => 'API no configurada'], 503);
        }

        if (strlen($numero) === 8) {
            $url   = "https://api.apis.net.pe/v2/reniec/dni?numero={$numero}";
            $campo = 'nombreCompleto';
        } elseif (strlen($numero) === 11) {
            $url   = "https://api.apis.net.pe/v2/sunat/ruc?numero={$numero}";
            $campo = 'razonSocial';
        } else {
            return response()->json(['success' => false, 'message' => 'Número inválido'], 422);
        }

        try {
            $response = Http::withToken($token)->timeout(5)->get($url);

            if ($response->successful()) {
                $nombre = $response->json($campo);
                if ($nombre) {
                    return response()->json(['success' => true, 'nombre' => $nombre]);
                }
            }

            return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
        } catch (\Exception $e) {
            Log::warning('consultarDocumento error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error de conexión'], 503);
        }
    }

    /**
     * Registra una nueva orden y descuenta stock atómicamente.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.code'      => 'required|exists:products,code',
            'items.*.qty'       => 'required|numeric|min:1',
            'items.*.price'     => 'required|numeric|min:0',
            'items.*.name'      => 'required|string',
            'payment_type'      => 'required|in:1,2,3,4',
            'voucher_type'      => 'required|in:1,2,3',
            'document_type_id'  => 'nullable|exists:document_types,id',
            'subtotal'          => 'nullable|numeric|min:0',
            'igv'               => 'nullable|numeric|min:0',
            'total'             => 'nullable|numeric|min:0',
        ]);

        // Factura requiere RUC (document_type_id = 3)
        if ($request->voucher_type == 2) {
            $docType = (int) $request->document_type_id;
            if ($docType !== DocumentType::RUC) {
                return response()->json([
                    'success' => false,
                    'message' => 'La factura requiere un cliente con RUC.',
                ], 422);
            }
            if (empty($request->customer_document)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La factura requiere ingresar el número de RUC.',
                ], 422);
            }
        }

        // Auto-detectar tipo de documento por longitud si no se envió
        $documentTypeId = $request->document_type_id
            ? (int) $request->document_type_id
            : DocumentType::detectarPorLongitud($request->customer_document ?? '');

        $employee = auth()->guard('employee')->user();

        // IGV y totales: se calculan aquí con la afectación real de cada producto
        $pricing = $this->pricing($request->items, $employee->company_id, $request->total);

        if (isset($pricing['error'])) {
            return response()->json(['success' => false, 'message' => $pricing['error']], 422);
        }

        ['tax' => $tax, 'units' => $units] = $pricing;

        // Caja histórica (si la venta se registra sobre una fecha pasada) o caja normal de la sesión.
        // En una caja histórica la venta y su kardex quedan con la fecha de esa caja.
        $historicalCaja = $request->attributes->get('cash_register');
        $cashRegisterId = $historicalCaja?->id ?? session('cash_register_id');
        $movedAt        = $historicalCaja
            ? Carbon::parse($historicalCaja->register_date)->setTimeFrom(now())
            : null;

        DB::beginTransaction();

        try {
            // Bloquear el stock de todos los productos en orden por código (evita bloqueos
            // cruzados entre ventas) y verificar que alcance antes de registrar nada
            $stocks = $this->lockStock($employee->branch_id, array_column($request->items, 'code'));

            if ($faltante = $this->insufficientStock($request->items, $stocks)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente para: ' . $faltante,
                ], 422);
            }

            // Generar número de comprobante automáticamente (atómico, dentro de la transacción)
            $typeCode      = DocumentSeries::typeCodeDesdeVoucher((int) $request->voucher_type);
            $voucherNumber = DocumentSeries::siguiente($typeCode, $employee->branch_id);

            // Crear la orden
            $order = new Order([
                'company_id'        => $employee->company_id,
                'branch_id'         => $employee->branch_id,
                'employee_id'       => $employee->id,
                'cash_register_id'  => $cashRegisterId,
                'customer_name'     => $request->customer_name ?: null,
                'document_type_id'  => $documentTypeId,
                'customer_document' => $request->customer_document ?: null,
                'voucher_type'      => $request->voucher_type,
                'voucher_number'    => $voucherNumber,
                'payment_type'      => $request->payment_type,
                'operation_number'  => $request->operation_number ?: null,
                'subtotal'          => $tax['subtotal'],
                'taxable_amount'    => $tax['taxable'],
                'exonerated_amount' => $tax['exonerated'],
                'unaffected_amount' => $tax['unaffected'],
                'igv'               => $tax['igv'],
                'total'             => $tax['total'],
                'status'            => 1,
            ]);

            if ($movedAt) {
                $order->created_at = $movedAt;
                $order->updated_at = $movedAt;
            }

            $order->save();

            // Registrar ítems, descontar stock en branch_stock y registrar kardex
            foreach ($request->items as $i => $item) {
                OrderItem::create([
                    'order_id'        => $order->id,
                    'product_code'    => $item['code'],
                    'product_name'    => $item['name'],
                    'unit_price'      => $item['price'],
                    'quantity'        => $item['qty'],
                    'subtotal'        => $tax['lines'][$i]['base'],
                    'igv_affectation' => $tax['lines'][$i]['affectation'],
                    'igv_amount'      => $tax['lines'][$i]['igv'],
                    'unit_code'       => $units[$item['code']],
                ]);

                // Descontar stock en branch_stock
                $branchStock = BranchStock::where('branch_id', $employee->branch_id)
                    ->where('product_code', $item['code'])
                    ->lockForUpdate()
                    ->first();

                $nuevoStock = $branchStock->stock_actual - $item['qty'];
                $branchStock->update(['stock_actual' => $nuevoStock]);

                // Registrar salida en el kardex
                $movement = new StockMovement([
                    'company_id'     => $employee->company_id,
                    'branch_id'      => $employee->branch_id,
                    'product_code'   => $item['code'],
                    'type'           => 'salida',
                    'reference_type' => 'order',
                    'reference_id'   => $order->id,
                    'quantity'       => (int) $item['qty'],
                    'unit_cost'      => $item['price'],
                    'balance'        => (int) $nuevoStock,
                ]);

                if ($movedAt) {
                    $movement->created_at = $movedAt;
                    $movement->updated_at = $movedAt;
                }

                $movement->save();
            }

            DB::commit();

            return response()->json([
                'success'        => true,
                'message'        => 'Venta registrada correctamente.',
                'order_id'       => $order->id,
                'voucher_number' => $order->voucher_number,
            ]);

        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Segunda capa de seguridad: la BD rechazó un número de comprobante duplicado
            DB::rollBack();
            Log::error('Número de comprobante duplicado: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error de correlativo duplicado. Intenta de nuevo.',
            ], 409);

        } catch (\RuntimeException $e) {
            // Serie no encontrada, inactiva o desbordada
            DB::rollBack();
            Log::error('Serie de documento no disponible: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar orden: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno al registrar la orden.',
            ], 500);
        }
    }

    /**
     * Muestra el formulario de edición de una orden histórica.
     * Solo se puede editar si la caja asociada está pendiente y abierta.
     */
    public function edit(Order $order)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($order->company_id !== $employee->company_id, 403);

        $cashRegister = $order->cashRegister;

        abort_if(!$cashRegister || !$cashRegister->isEditable(), 403,
            'Esta orden no se puede editar. La caja ya fue cerrada o validada.');

        abort_if($cashRegister->employee_id !== $employee->id, 403);

        $order->load('items');

        // Productos disponibles en la sede (para agregar nuevos ítems)
        $branchId = $employee->branch_id;
        $products = Product::with([
                'laboratory',
                'presentations.unit',
                'branchStocks' => fn ($q) => $q->where('branch_id', $branchId),
            ])
            ->where('company_id', $employee->company_id)
            ->where('status', 1)
            ->whereHas('branchStocks', fn ($q) => $q->where('branch_id', $branchId)->where('stock_actual', '>', 0))
            ->orderBy('name')
            ->get();

        $documentTypes = DocumentType::activos()->get();

        // Configuración de impresión de la sede (la vista del POS la necesita)
        $branch      = $employee->branch()->with('config')->first();
        $printConfig = array_merge([
            'default_template' => 'ticket_80mm',
            'auto_print'       => false,
        ], $branch->getSettingGroup('printing'));

        // Ítems actuales de la orden para precargar el carrito. El stock máximo de cada
        // uno es el stock actual de la sede más lo que la orden ya tiene descontado.
        $stocks = BranchStock::where('branch_id', $branchId)
            ->whereIn('product_code', $order->items->pluck('product_code'))
            ->pluck('stock_actual', 'product_code');

        // Afectación actual de cada producto (el total se recalcula al guardar)
        $affectations = Product::whereIn('code', $order->items->pluck('product_code'))
            ->pluck('igv_affectation', 'code');

        $editItems = $order->items->map(fn ($item) => [
            'code'       => $item->product_code,
            'name'       => $item->product_name,
            'price'      => (float) $item->unit_price,
            'qty'        => (int) $item->quantity,
            'stock'      => (int) (($stocks[$item->product_code] ?? 0) + $item->quantity),
            'afectacion' => $affectations[$item->product_code] ?? '20',
        ])->values();

        return view('employee.pages.orders.index', [
            'products'       => $products,
            'documentTypes'  => $documentTypes,
            'printConfig'    => $printConfig,
            'historicalCaja' => $cashRegister,
            'editOrder'      => $order,
            'editItems'      => $editItems,
        ]);
    }

    /**
     * Actualiza una orden histórica pendiente.
     * Ajusta stock: devuelve el stock de los ítems anteriores y descuenta los nuevos.
     */
    public function updateHistorical(Request $request, Order $order)
    {
        $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.code'      => 'required|exists:products,code',
            'items.*.qty'       => 'required|numeric|min:1',
            'items.*.price'     => 'required|numeric|min:0',
            'items.*.name'      => 'required|string',
            'payment_type'      => 'required|in:1,2,3,4',
            'subtotal'          => 'nullable|numeric|min:0',
            'igv'               => 'nullable|numeric|min:0',
            'total'             => 'nullable|numeric|min:0',
        ]);

        $employee     = auth()->guard('employee')->user();
        $cashRegister = $order->cashRegister;

        abort_if($order->company_id !== $employee->company_id, 403);
        abort_if(!$cashRegister || !$cashRegister->isEditable(), 403);
        abort_if($cashRegister->employee_id !== $employee->id, 403);

        // IGV y totales: se recalculan con la afectación real de cada producto
        $pricing = $this->pricing($request->items, $employee->company_id, $request->total);

        if (isset($pricing['error'])) {
            return response()->json(['success' => false, 'message' => $pricing['error']], 422);
        }

        ['tax' => $tax, 'units' => $units] = $pricing;

        DB::beginTransaction();
        try {
            // 0. Bloquear de una vez el stock de los ítems actuales y nuevos, en orden por código
            $stocks = $this->lockStock($employee->branch_id, array_merge(
                $order->items->pluck('product_code')->all(),
                array_column($request->items, 'code')
            ));

            // 1. Devolver stock de los ítems actuales de la orden
            foreach ($order->items as $oldItem) {
                $branchStock = BranchStock::where('branch_id', $employee->branch_id)
                    ->where('product_code', $oldItem->product_code)
                    ->lockForUpdate()
                    ->first();

                if ($branchStock) {
                    $stockRestored = $branchStock->stock_actual + $oldItem->quantity;
                    $branchStock->update(['stock_actual' => $stockRestored]);

                    StockMovement::create([
                        'company_id'     => $employee->company_id,
                        'branch_id'      => $employee->branch_id,
                        'product_code'   => $oldItem->product_code,
                        'type'           => 'entrada',
                        'reference_type' => 'order_edit_reversal',
                        'reference_id'   => $order->id,
                        'quantity'       => (int) $oldItem->quantity,
                        'unit_cost'      => $oldItem->unit_price,
                        'balance'        => (int) $stockRestored,
                    ]);
                }
            }

            // 2. Verificar stock suficiente para los nuevos ítems (con el stock ya devuelto)
            $stocks = BranchStock::where('branch_id', $employee->branch_id)
                ->whereIn('product_code', array_column($request->items, 'code'))
                ->get()
                ->keyBy('product_code');

            if ($faltante = $this->insufficientStock($request->items, $stocks)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente para: ' . $faltante,
                ], 422);
            }

            // 3. Eliminar ítems anteriores y crear los nuevos
            $order->items()->delete();

            foreach ($request->items as $i => $item) {
                OrderItem::create([
                    'order_id'        => $order->id,
                    'product_code'    => $item['code'],
                    'product_name'    => $item['name'],
                    'unit_price'      => $item['price'],
                    'quantity'        => $item['qty'],
                    'subtotal'        => $tax['lines'][$i]['base'],
                    'igv_affectation' => $tax['lines'][$i]['affectation'],
                    'igv_amount'      => $tax['lines'][$i]['igv'],
                    'unit_code'       => $units[$item['code']],
                ]);

                $branchStock  = BranchStock::where('branch_id', $employee->branch_id)
                    ->where('product_code', $item['code'])
                    ->lockForUpdate()
                    ->first();

                $nuevoStock = $branchStock->stock_actual - $item['qty'];
                $branchStock->update(['stock_actual' => $nuevoStock]);

                StockMovement::create([
                    'company_id'     => $employee->company_id,
                    'branch_id'      => $employee->branch_id,
                    'product_code'   => $item['code'],
                    'type'           => 'salida',
                    'reference_type' => 'order_edit',
                    'reference_id'   => $order->id,
                    'quantity'       => (int) $item['qty'],
                    'unit_cost'      => $item['price'],
                    'balance'        => (int) $nuevoStock,
                ]);
            }

            // 4. Actualizar totales de la orden
            $order->update([
                'payment_type'      => $request->payment_type,
                'operation_number'  => $request->operation_number ?: null,
                'subtotal'          => $tax['subtotal'],
                'taxable_amount'    => $tax['taxable'],
                'exonerated_amount' => $tax['exonerated'],
                'unaffected_amount' => $tax['unaffected'],
                'igv'               => $tax['igv'],
                'total'             => $tax['total'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizada correctamente.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al editar orden histórica: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno al actualizar la orden.',
            ], 500);
        }
    }
}
