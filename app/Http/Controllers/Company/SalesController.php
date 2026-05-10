<?php

namespace App\Http\Controllers\Company;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sales;
use App\Models\SaleDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
    public function index()
    {
        $productos = Product::with(['laboratorio', 'presentaciones.unidadMedida'])
            ->where('status', 1)
            ->where('stock_actual', '>', 0)
            ->orderBy('came')
            ->get();

        return view('company.pages.sales.index', compact('productos'));
    }

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

    public function store(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.code'       => 'required|exists:products,code',
            'items.*.qty'        => 'required|numeric|min:1',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.name'       => 'required|string',
            'payment_type'       => 'required|in:1,2,3,4',
            'voucher_type'       => 'required|in:1,2,3',
            'subtotal'           => 'required|numeric|min:0',
            'igv'                => 'required|numeric|min:0',
            'total'              => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Verificar stock antes de descontar
            foreach ($request->items as $item) {
                $producto = Product::where('code', $item['code'])->lockForUpdate()->first();

                if (!$producto || $producto->stock_actual < $item['qty']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock insuficiente para: ' . ($item['name'] ?? $item['code']),
                    ], 422);
                }
            }

            $venta = Sales::create([
                'customer_name'     => $request->customer_name ?: null,
                'customer_document' => $request->customer_document ?: null,
                'voucher_type'      => $request->voucher_type,
                'voucher_number'    => $request->voucher_number ?: null,
                'payment_type'      => $request->payment_type,
                'operation_number'  => $request->operation_number ?: null,
                'subtotal'          => $request->subtotal,
                'igv'               => $request->igv,
                'total'             => $request->total,
                'status'            => 1,
            ]);

            foreach ($request->items as $item) {
                SaleDetail::create([
                    'sale_id'      => $venta->id,
                    'product_code' => $item['code'],
                    'product_name' => $item['name'],
                    'unit_price'   => $item['price'],
                    'quantity'     => $item['qty'],
                    'subtotal'     => round($item['price'] * $item['qty'], 2),
                ]);

                Product::where('code', $item['code'])
                    ->decrement('stock_actual', $item['qty']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta registrada correctamente.',
                'sale_id' => $venta->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar venta: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno al registrar la venta.',
            ], 500);
        }
    }
}
