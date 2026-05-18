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
use Illuminate\Http\Request;
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

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('employee.pages.orders.historial', compact('orders'));
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
    public function index()
    {
        $employee = auth()->guard('employee')->user();

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

        return view('employee.pages.orders.index', compact('products', 'documentTypes', 'printConfig'));
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
            'subtotal'          => 'required|numeric|min:0',
            'igv'               => 'required|numeric|min:0',
            'total'             => 'required|numeric|min:0',
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

        DB::beginTransaction();

        try {
            // Verificar stock en branch_stock antes de registrar nada
            foreach ($request->items as $item) {
                $stock = BranchStock::where('branch_id', $employee->branch_id)
                    ->where('product_code', $item['code'])
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->stock_actual < $item['qty']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock insuficiente para: ' . ($item['name'] ?? $item['code']),
                    ], 422);
                }
            }

            // Generar número de comprobante automáticamente (atómico, dentro de la transacción)
            $typeCode      = DocumentSeries::typeCodeDesdeVoucher((int) $request->voucher_type);
            $voucherNumber = DocumentSeries::siguiente($typeCode);

            // Crear la orden
            $order = Order::create([
                'company_id'        => $employee->company_id,
                'branch_id'         => $employee->branch_id,
                'employee_id'       => $employee->id,
                'cash_register_id'  => session('cash_register_id'),
                'customer_name'     => $request->customer_name ?: null,
                'document_type_id'  => $documentTypeId,
                'customer_document' => $request->customer_document ?: null,
                'voucher_type'      => $request->voucher_type,
                'voucher_number'    => $voucherNumber,
                'payment_type'      => $request->payment_type,
                'operation_number'  => $request->operation_number ?: null,
                'subtotal'          => $request->subtotal,
                'igv'               => $request->igv,
                'total'             => $request->total,
                'status'            => 1,
            ]);

            // Registrar ítems, descontar stock en branch_stock y registrar kardex
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_code' => $item['code'],
                    'product_name' => $item['name'],
                    'unit_price'   => $item['price'],
                    'quantity'     => $item['qty'],
                    'subtotal'     => round($item['price'] * $item['qty'], 2),
                ]);

                // Descontar stock en branch_stock
                $branchStock = BranchStock::where('branch_id', $employee->branch_id)
                    ->where('product_code', $item['code'])
                    ->lockForUpdate()
                    ->first();

                $nuevoStock = $branchStock->stock_actual - $item['qty'];
                $branchStock->update(['stock_actual' => $nuevoStock]);

                // Registrar salida en el kardex
                StockMovement::create([
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
}
