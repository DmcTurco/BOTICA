<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\BranchStock;
use App\Models\Client;
use App\Models\DocumentSeries;
use App\Models\DocumentType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\StockMovement;
use App\Jobs\SendOrderToSunat;
use App\Models\CompanySunatSetting;
use App\Services\Sunat\Contracts\SunatGateway;
use App\Services\BatchService;
use App\Services\Requirements;
use App\Services\Sunat\TaxCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Muestra el historial de órdenes con filtros.
     */
    public function historial(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $query = Order::with('creditNote')
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->orderBy('created_at', 'desc');

        // "=id" solo compara el N° de venta cuando el texto es numérico (evita error de tipo en PostgreSQL)
        $query->search($request->buscar, ['customer_name', 'customer_document', 'voucher_number', '=id'])
              ->filterBy(['tipo_comprobante' => 'voucher_type', 'tipo_pago' => 'payment_type']);

        // Rango de fechas: por defecto del 1er al último día del mes actual
        $fechaDesde = $request->input('fecha_desde') ?: now()->startOfMonth()->toDateString();
        $fechaHasta = $request->input('fecha_hasta') ?: now()->endOfMonth()->toDateString();

        $query->whereDate('created_at', '>=', $fechaDesde)
              ->whereDate('created_at', '<=', $fechaHasta);

        $orders = $query->paginate(15)->withQueryString();

        // Con resumen diario las boletas no se reenvían una por una: se muestra el botón del resumen
        $boletaSummary = (bool) CompanySunatSetting::where('company_id', $employee->company_id)
            ->first()?->usesBoletaSummary();

        return view('employee.pages.orders.historial', compact('orders', 'fechaDesde', 'fechaHasta', 'boletaSummary'));
    }

    /**
     * Reenvía a SUNAT una boleta o factura pendiente o con error de envío.
     * Se ejecuta en el momento para mostrar el resultado al usuario.
     */
    public function resendSunat(Order $order, SunatGateway $gateway)
    {
        $employee = auth()->guard('employee')->user();

        abort_if(
            $order->company_id !== $employee->company_id ||
            $order->branch_id  !== $employee->branch_id,
            403
        );

        if (!$order->canResendToSunat()) {
            return response()->json([
                'success' => false,
                'message' => 'Este comprobante no se puede reenviar a SUNAT.',
            ], 422);
        }

        // Con resumen diario las boletas se informan juntas, no una por una
        if ($order->isBoleta() && CompanySunatSetting::where('company_id', $order->company_id)->first()?->usesBoletaSummary()) {
            return response()->json([
                'success' => false,
                'message' => 'Las boletas se envían a SUNAT en el resumen diario. Usa "Enviar resumen de boletas".',
            ], 422);
        }

        $updated = (new SendOrderToSunat($order->id))->handle($gateway);

        return response()->json([
            'success' => $updated?->sunat_status === Order::SUNAT_ACCEPTED,
            'status'  => $updated?->sunat_status,
            'label'   => $updated?->sunatLabel(),
            'message' => $updated?->sunat_message ?? 'El comprobante ya se está enviando.',
        ]);
    }

    /**
     * SUNAT exige identificar al cliente (apellidos y nombres, tipo y número de documento)
     * en las boletas cuyo total excede S/ 700. Devuelve el mensaje de error o null si cumple.
     */
    private function boletaIdentificationError(float $total, ?string $name, ?int $documentTypeId, ?string $document): ?string
    {
        if ($total <= Order::BOLETA_ID_THRESHOLD) {
            return null;
        }

        $message = 'Las boletas de más de S/ ' . Order::BOLETA_ID_THRESHOLD . ' requieren identificar al cliente: nombres y apellidos, tipo y número de documento.';

        // El cliente público (DNI 00000000) no cuenta como identificado
        if (blank($name) || blank($document) || $document === Client::PUBLIC_DOCUMENT
            || !$documentTypeId || $documentTypeId === DocumentType::SIN_DOCUMENTO) {
            return $message;
        }

        // El número debe tener la longitud propia de su tipo (p. ej. DNI de 8 dígitos)
        $digits = DocumentType::whereKey($documentTypeId)->value('digits');

        if ($digits && strlen(preg_replace('/\D/', '', $document)) !== (int) $digits) {
            return "El número de documento debe tener {$digits} dígitos.";
        }

        return null;
    }

    /**
     * ¿La compañía tiene la facturación electrónica activa y completa?
     */
    private function sunatEnabled(int $companyId): bool
    {
        $setting = CompanySunatSetting::where('company_id', $companyId)->first();

        return $setting && $setting->enabled && $setting->isReady();
    }

    /**
     * El POS ofrece solo stock vendible: a lo que hay en la sede se le restan las unidades de lotes
     * vencidos, y los productos que quedan en 0 desaparecen de la lista. (Solo en memoria; no se guarda.)
     */
    private function withSellableStock(\Illuminate\Support\Collection $products, int $branchId): \Illuminate\Support\Collection
    {
        $expired = app(BatchService::class)->expiredMap($branchId, $products->pluck('code')->all());

        return $products
            ->each(function (Product $p) use ($expired) {
                if ($stock = $p->branchStocks->first()) {
                    $stock->stock_actual = max(0, (float) $stock->stock_actual - ($expired[$p->code] ?? 0));
                }
            })
            ->filter(fn (Product $p) => (float) ($p->branchStocks->first()?->stock_actual ?? 0) > 0)
            ->values();
    }

    /**
     * Reglas del fiado: solo para un cliente registrado con línea de crédito disponible, y no en facturas
     * (una factura al crédito exige declarar las cuotas a SUNAT, que este sistema aún no envía).
     * Devuelve [cliente|null, mensaje de error|null].
     */
    private function creditClient(Request $request, int $companyId, float $total): array
    {
        if ((int) $request->voucher_type === 2) {
            return [null, 'Las facturas no se emiten al crédito. Usa boleta o nota de venta para el fiado.'];
        }

        $client = $request->filled('client_id')
            ? Client::where('company_id', $companyId)->activos()->find($request->client_id)
            : null;

        if (!$client || $client->isPublic()) {
            return [null, 'Para vender a crédito debes seleccionar un cliente registrado (no el cliente público).'];
        }
        if ((float) $client->credit_limit <= 0) {
            return [null, 'El cliente «' . $client->name . '» no tiene línea de crédito. Asígnala en su ficha de cliente.'];
        }
        if ($total > $client->creditAvailable()) {
            return [null, 'Supera el crédito disponible de «' . $client->name . '»: puede usar S/ ' . number_format($client->creditAvailable(), 2) . ' y la venta es de S/ ' . number_format($total, 2) . '.'];
        }

        return [$client, null];
    }

    /** Cliente enviado por el POS solo si pertenece a la compañía (si no, la venta queda sin cliente enlazado) */
    private function validClientId(Request $request, int $companyId): ?int
    {
        return $request->filled('client_id')
            ? Client::where('company_id', $companyId)->whereKey($request->client_id)->value('id')
            : null;
    }

    /**
     * Nivel de receta más alto entre los productos vendidos: 0 libre · 1 exige receta · 2 controlado.
     * Se lee de la base de datos, nunca del navegador.
     */
    private function recipeLevel(array $items, int $companyId): int
    {
        return (int) Product::where('company_id', $companyId)
            ->whereIn('code', array_column($items, 'code'))
            ->get(['code', 'requires_recipe', 'controlled_type'])
            ->max(fn (Product $p) => $p->recipe_level);
    }

    /**
     * Valida los datos de la receta que acompañan a la venta. En productos controlados también
     * son obligatorios el documento del paciente y el número de receta.
     * Devuelve [datos validados|null, mensaje de error|null].
     */
    private function prescriptionData(Request $request, int $level): array
    {
        $strict = $level >= 2;

        $validator = Validator::make((array) $request->input('prescription'), [
            'patient_name'        => 'required|string|max:150',
            'patient_document'    => ($strict ? 'required' : 'nullable') . '|string|max:15',
            'doctor_name'         => 'required|string|max:150',
            'doctor_license'      => 'required|string|max:20',
            'establishment'       => 'nullable|string|max:150',
            'prescription_number' => ($strict ? 'required' : 'nullable') . '|string|max:30',
            'prescription_date'   => 'required|date|before_or_equal:today',
        ], [
            'patient_name.required'        => 'falta el nombre del paciente.',
            'patient_document.required'    => 'falta el documento del paciente.',
            'doctor_name.required'         => 'falta el nombre del médico.',
            'doctor_license.required'      => 'falta el N° de colegiatura (CMP) del médico.',
            'prescription_number.required' => 'falta el número de receta.',
            'prescription_date.required'   => 'falta la fecha de la receta.',
            'prescription_date.before_or_equal' => 'la fecha de la receta no puede ser futura.',
        ]);

        if ($validator->fails()) {
            return [null, 'Esta venta incluye productos ' . ($strict ? 'controlados' : 'con receta') . ': ' . $validator->errors()->first()];
        }

        return [$validator->validated(), null];
    }

    /** Descuento máximo que se puede dar en una venta (%) */
    const MAX_DISCOUNT_PERCENT = 50;

    /** Ajustes de venta de la sede (apagados por defecto): redondeo en efectivo y descuento por monto */
    private function salesSettings(): array
    {
        $branch = auth()->guard('employee')->user()->branch()->with('config')->first();

        return array_merge(['cash_rounding' => false, 'amount_discount' => false], $branch?->getSettingGroup('sales') ?? []);
    }

    /**
     * Redondeo en efectivo: el total se cobra hacia abajo al múltiplo de S/ 0.10 más cercano
     * (la moneda más pequeña que circula). Solo con pago en efectivo y si la sede lo activó.
     * Devuelve los céntimos que se perdonan al cliente (0.00 a 0.09).
     */
    private function cashRounding(float $total, int $paymentType): float
    {
        if ($paymentType !== \App\Models\CashRegister::PAYMENT_CASH || !$this->salesSettings()['cash_rounding']) {
            return 0.0;
        }

        return round(((int) round($total * 100) % 10) / 100, 2);
    }

    /**
     * Efectivo con el que pagó el cliente. Solo aplica al pago en efectivo y es opcional.
     * Debe cubrir lo que se cobra (total menos el redondeo). Devuelve [monto|null, mensaje de error|null].
     */
    private function cashReceived(Request $request, float $total): array
    {
        $paymentType = (int) $request->payment_type;

        if ($paymentType !== \App\Models\CashRegister::PAYMENT_CASH || !is_numeric($request->amount_received) || (float) $request->amount_received <= 0) {
            return [null, null];
        }

        $received = round((float) $request->amount_received, 2);
        $due      = round($total - $this->cashRounding($total, $paymentType), 2);

        if ($received < $due) {
            return [null, 'El efectivo recibido (S/ ' . number_format($received, 2) . ') es menor al total a cobrar (S/ ' . number_format($due, 2) . ').'];
        }

        return [$received, null];
    }

    /**
     * Descuento pedido por el cajero: porcentaje entero o monto en soles (no ambos).
     * Solo lo aplica quien tiene el privilegio de descuentos; sin él se ignora lo enviado
     * (nunca se confía en el navegador). El monto además debe estar activado en la configuración.
     * Devuelve [porcentaje equivalente, monto fijo|null, mensaje de error|null].
     */
    private function discountInput(Request $request, array $items): array
    {
        $percent = (float) (int) $request->input('discount_percent', 0);
        $value   = round((float) $request->input('discount_value', 0), 2);

        if ($percent <= 0 && $value <= 0) {
            return [0.0, null, null];
        }

        if (!auth()->guard('employee')->user()->hasPrivilege(\App\Models\Employee::PRIV_APLICAR_DESCUENTO)) {
            return [0.0, null, 'No tienes permiso para aplicar descuentos.'];
        }

        if ($percent > 0 && $value > 0) {
            return [0.0, null, 'Elige el descuento en porcentaje o en monto, no los dos a la vez.'];
        }

        if ($value > 0) {
            if (!$this->salesSettings()['amount_discount']) {
                return [0.0, null, 'El descuento por monto no está activado en la configuración de la sede.'];
            }

            $gross = array_sum(array_map(fn ($i) => round((float) $i['price'] * (float) $i['qty'], 2), $items));
            $max   = round($gross * self::MAX_DISCOUNT_PERCENT / 100, 2);

            if ($gross <= 0 || $value > $max) {
                return [0.0, null, 'El descuento máximo permitido es ' . self::MAX_DISCOUNT_PERCENT . '% de la venta (S/ ' . number_format($max, 2) . ').'];
            }

            // Se guarda también el porcentaje equivalente (para reportes y comprobantes)
            return [round($value / $gross * 100, 2), $value, null];
        }

        if ($percent > self::MAX_DISCOUNT_PERCENT) {
            return [0.0, null, 'El descuento máximo permitido es ' . self::MAX_DISCOUNT_PERCENT . '%.'];
        }

        return [$percent, null, null];
    }

    /**
     * Arma las líneas de la venta con los datos de la base de datos (nunca del navegador): el
     * navegador solo indica producto, presentación y cantidad. El precio, las unidades base que
     * trae la presentación (blíster x10 = 10), su unidad SUNAT y el nombre salen de aquí.
     * Una línea por producto + presentación: si llegan repetidas se suman, así la unidad y el
     * blíster del mismo producto quedan en líneas separadas, cada una con su precio.
     *
     * Compatibilidad: un POS abierto antes de esta versión no envía presentation_id; si el precio
     * enviado no es el de la unidad y coincide con una sola presentación activa, se usa esa.
     *
     * Devuelve ['lines' => [[code, name, qty, price, presentation_id, factor, units, unit_code], ...]]
     * o ['error' => mensaje].
     */
    private function resolveLines(array $items, int $companyId): array
    {
        $products = Product::with(['unit:id,sunat_code', 'presentations.unit:id,name,sunat_code'])
            ->where('company_id', $companyId)
            ->whereIn('code', array_unique(array_column($items, 'code')))
            ->get()
            ->keyBy('code');

        $lines = [];

        foreach ($items as $item) {
            $product = $products->get($item['code']);

            if (!$product) {
                return ['error' => 'Hay productos que no pertenecen a tu compañía.'];
            }

            $active       = $product->presentations->where('status', 1);
            $presentation = null;
            $sentPrice    = isset($item['price']) ? (float) $item['price'] : null;

            if (!empty($item['presentation_id'])) {
                $presentation = $active->firstWhere('id', (int) $item['presentation_id']);

                if (!$presentation) {
                    return ['error' => "La presentación elegida de «{$product->name}» ya no está disponible. Actualiza la página e inténtalo de nuevo."];
                }
            } elseif ($sentPrice !== null && ($product->unit_sale_price === null || abs((float) $product->unit_sale_price - $sentPrice) >= 0.005)) {
                $matches = $active->filter(fn ($p) => abs((float) $p->sale_price - $sentPrice) < 0.005);

                if ($matches->count() !== 1) {
                    return ['error' => "El precio de «{$product->name}» no coincide con el precio actual. Actualiza la página e inténtalo de nuevo."];
                }

                $presentation = $matches->first();
            }

            $price  = $presentation ? (float) $presentation->sale_price : (float) $product->unit_sale_price;
            $factor = $presentation ? (float) $presentation->equivalent_amount : 1.0;

            if ($factor <= 0 || ($presentation === null && $product->unit_sale_price === null)) {
                return ['error' => "«{$product->name}» no tiene precio de venta configurado."];
            }

            $key = $product->code . '|' . ($presentation?->id ?? 'base');

            if (isset($lines[$key])) {
                $lines[$key]['qty']  += (float) $item['qty'];
                $lines[$key]['units'] = round($lines[$key]['qty'] * $factor, 2);
                continue;
            }

            // El nombre incluye la presentación: así sale en el comprobante, SUNAT y los reportes
            $label = $presentation?->unit?->name ?? 'Presentación';
            $name  = $presentation
                ? $product->name . ' (' . $label . ($factor != 1 ? ' x' . rtrim(rtrim(number_format($factor, 2, '.', ''), '0'), '.') : '') . ')'
                : $product->name;

            $lines[$key] = [
                'code'            => $product->code,
                'name'            => mb_substr($name, 0, 150),
                'qty'             => (float) $item['qty'],
                'price'           => $price,
                'presentation_id' => $presentation?->id,
                'factor'          => $factor,
                'units'           => round((float) $item['qty'] * $factor, 2),
                'unit_code'       => $presentation?->unit?->sunat_code ?: ($product->unit?->sunat_code ?: 'NIU'),
            ];
        }

        return ['lines' => array_values($lines)];
    }

    /**
     * Calcula el IGV y los totales de una venta con la afectación de cada producto
     * leída de la base de datos (nunca del navegador). Las líneas vienen de resolveLines(),
     * así que sus precios ya son los de la base de datos.
     * Devuelve ['tax' => cálculo] o ['error' => mensaje] si los totales no coinciden.
     */
    private function pricing(array $lines, int $companyId, mixed $clientTotal, float $discountPercent = 0, ?float $discountAmount = null): array
    {
        $affectations = Product::where('company_id', $companyId)
            ->whereIn('code', array_unique(array_column($lines, 'code')))
            ->pluck('igv_affectation', 'code')
            ->all();

        $tax = app(TaxCalculator::class)->calculate($lines, $affectations, $discountPercent, $discountAmount);

        // El total que vio el cajero debe coincidir con el del servidor (tolerancia por redondeo)
        if ($clientTotal !== null && abs($tax['total'] - (float) $clientTotal) > 0.01 * (count($lines) + 1)) {
            return ['error' => 'Los totales no coinciden con los precios actuales. Actualiza la página e inténtalo de nuevo.'];
        }

        return ['tax' => $tax];
    }

    /**
     * Registra una línea de la venta: el ítem (con su presentación), la salida de stock en
     * unidades base (2 blísteres x10 = 20), su kardex y el consumo del lote que vence primero (FEFO).
     * El stock de la sede ya debe estar bloqueado y verificado (lockStock + insufficientStock).
     */
    private function saveLine(Order $order, array $line, array $taxLine, $employee, string $referenceType, BatchService $batches, ?Carbon $movedAt = null): void
    {
        OrderItem::create([
            'order_id'        => $order->id,
            'product_code'    => $line['code'],
            'product_name'    => $line['name'],
            'presentation_id' => $line['presentation_id'],
            'units_per_item'  => $line['factor'],
            'unit_price'      => $line['price'],
            'quantity'        => $line['qty'],
            'discount_amount' => $taxLine['discount'],
            'subtotal'        => $taxLine['base'],
            'igv_affectation' => $taxLine['affectation'],
            'igv_amount'      => $taxLine['igv'],
            'unit_code'       => $line['unit_code'],
        ]);

        $branchStock = BranchStock::where('branch_id', $employee->branch_id)
            ->where('product_code', $line['code'])
            ->lockForUpdate()
            ->first();

        $nuevoStock = $branchStock->stock_actual - $line['units'];
        $branchStock->update(['stock_actual' => $nuevoStock]);

        // Kardex en unidades base; el costo registrado es el precio por unidad base
        $movement = new StockMovement([
            'company_id'     => $employee->company_id,
            'branch_id'      => $employee->branch_id,
            'product_code'   => $line['code'],
            'type'           => 'salida',
            'reference_type' => $referenceType,
            'reference_id'   => $order->id,
            'quantity'       => (int) round($line['units']),
            'unit_cost'      => round($line['price'] / $line['factor'], 2),
            'balance'        => (int) $nuevoStock,
        ]);

        if ($movedAt) {
            $movement->created_at = $movedAt;
            $movement->updated_at = $movedAt;
        }

        $movement->save();

        $batches->consume($employee->branch_id, $line['code'], (float) $line['units'], 'order', $order->id);
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
     * Verifica que el stock alcance para las líneas, en unidades base y sumando todas las
     * líneas del mismo producto (ej: 3 tabletas + 1 blíster x10 = 13 tabletas).
     * Devuelve el nombre del primer producto sin stock suficiente, o null si todo alcanza.
     */
    private function insufficientStock(array $lines, \Illuminate\Support\Collection $stocks, array $expired = []): ?string
    {
        $required = [];
        $names    = [];

        foreach ($lines as $line) {
            $code            = $line['code'];
            $required[$code] = ($required[$code] ?? 0) + (float) ($line['units'] ?? $line['qty']);
            $names[$code]  ??= $line['name'] ?? $code;
        }

        foreach ($required as $code => $qty) {
            $stock = $stocks->get($code);

            // Las unidades de lotes vencidos no se pueden vender
            if (!$stock || (float) $stock->stock_actual - ($expired[$code] ?? 0) < $qty) {
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
            'discount_amount'   => $order->discount_amount,
            'discount_percent'  => $order->discount_percent,
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

        $products = $this->withSellableStock($products, $branchId);

        // Comprobantes con serie activa en la sede (los demás no se muestran)
        $availableVouchers = $this->availableVouchers($branchId);

        $requirements = Requirements::make('Ventas', 'employee.home')
            ->need(Product::where('company_id', $employee->company_id)->exists(), 'Productos', 'Registra los productos que vas a vender.', 'employee.products.create', 'Registrar producto')
            ->need($products->isNotEmpty(), 'Stock en esta sede', 'Ningún producto tiene stock en tu sede. Ingrésalo registrando una compra.', 'employee.purchases.create', 'Registrar compra')
            ->need(!empty($availableVouchers), 'Series de comprobantes de la sede', 'La empresa debe generarlas en Empresa → Sedes → "Generar series".');

        if ($requirements->blocked()) {
            return $requirements->view();
        }

        // Tipos de documento SUNAT: datos fijos del sistema, se crean si no se corrió el seeder
        if (!DocumentType::exists()) {
            DocumentType::crearTiposBase();
        }

        $documentTypes = DocumentType::activos()->get();

        // Configuración de impresión de la sede (para el modal post-venta)
        $branch      = $employee->branch()->with('config')->first();
        $printConfig = array_merge([
            'default_template' => 'ticket_80mm',
            'auto_print'       => false,
        ], $branch->getSettingGroup('printing'));

        // Cliente predeterminado del POS (ventas al público general)
        $publicClient = Client::publicFor($employee->company_id);

        return view('employee.pages.orders.index', compact('products', 'documentTypes', 'printConfig', 'historicalCaja', 'publicClient', 'availableVouchers'));
    }

    /**
     * Tipos de comprobante (voucher_type de orders) que la sede puede emitir:
     * los que tienen una serie activa. Ej.: [3, 1] si falta la serie de factura.
     *
     * @return array<int, int>
     */
    private function availableVouchers(int $branchId): array
    {
        $activeTypes = DocumentSeries::where('branch_id', $branchId)
            ->where('active', true)
            ->pluck('type_code')
            ->all();

        return array_keys(array_filter(
            DocumentSeries::VOUCHER_TYPE_MAP,
            fn ($typeCode) => in_array($typeCode, $activeTypes, true)
        ));
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
            'items.*.presentation_id' => 'nullable|integer',
            'items.*.price'     => 'required|numeric|min:0',
            'items.*.name'      => 'required|string',
            'payment_type'      => 'required|in:1,2,3,4,5',
            'credit_due_date'   => 'nullable|date|after_or_equal:today',
            'client_id'         => 'nullable|integer',
            'voucher_type'      => 'required|in:1,2,3',
            'document_type_id'  => 'nullable|exists:document_types,id',
            'subtotal'          => 'nullable|numeric|min:0',
            'igv'               => 'nullable|numeric|min:0',
            'total'             => 'nullable|numeric|min:0',
            'discount_percent'  => 'nullable|integer|min:0|max:100',
            'discount_value'    => 'nullable|numeric|min:0|max:99999999',
            'amount_received'   => 'nullable|numeric|min:0|max:99999999',
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

        // Una venta no se cierra sin cliente: si no se eligió ninguno (y no es factura) se usa el cliente público
        if ((int) $request->voucher_type !== 2
            && blank($request->client_id) && blank($request->customer_name) && blank($request->customer_document)) {
            $public = Client::publicFor(auth()->guard('employee')->user()->company_id);

            $request->merge([
                'client_id'         => $public->id,
                'customer_name'     => $public->name,
                'document_type_id'  => $public->document_type_id,
                'customer_document' => $public->document_number,
            ]);
        }

        // Auto-detectar tipo de documento por longitud si no se envió
        $documentTypeId = $request->document_type_id
            ? (int) $request->document_type_id
            : DocumentType::detectarPorLongitud($request->customer_document ?? '');

        $employee = auth()->guard('employee')->user();

        // Líneas con precio, presentación y unidades base tomados de la base de datos
        $resolved = $this->resolveLines($request->items, $employee->company_id);

        if (isset($resolved['error'])) {
            return response()->json(['success' => false, 'message' => $resolved['error']], 422);
        }

        $lines = $resolved['lines'];

        [$discountPercent, $discountAmount, $discountError] = $this->discountInput($request, $lines);

        if ($discountError) {
            return response()->json(['success' => false, 'message' => $discountError], 422);
        }

        // IGV y totales: se calculan aquí con la afectación real de cada producto
        $pricing = $this->pricing($lines, $employee->company_id, $request->total, $discountPercent, $discountAmount);

        if (isset($pricing['error'])) {
            return response()->json(['success' => false, 'message' => $pricing['error']], 422);
        }

        $tax = $pricing['tax'];

        // Efectivo recibido (solo pago en efectivo): debe cubrir lo que se cobra
        [$amountReceived, $receivedError] = $this->cashReceived($request, $tax['total']);

        if ($receivedError) {
            return response()->json(['success' => false, 'message' => $receivedError], 422);
        }

        // Boleta de más de S/ 700: el cliente debe estar identificado
        if ((int) $request->voucher_type === 1) {
            $error = $this->boletaIdentificationError(
                $tax['total'], $request->customer_name, $documentTypeId, $request->customer_document
            );

            if ($error) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }
        }

        // Venta a crédito (fiado): cliente registrado con línea disponible
        $creditClient = null;

        if ((int) $request->payment_type === \App\Models\CashRegister::PAYMENT_CREDIT) {
            [$creditClient, $creditError] = $this->creditClient($request, $employee->company_id, $tax['total']);

            if ($creditError) {
                return response()->json(['success' => false, 'message' => $creditError], 422);
            }
        }

        // Productos con receta o controlados: la venta exige los datos de la receta
        $rxLevel          = $this->recipeLevel($lines, $employee->company_id);
        $prescriptionData = null;

        if ($rxLevel > 0) {
            [$prescriptionData, $rxError] = $this->prescriptionData($request, $rxLevel);

            if ($rxError) {
                return response()->json(['success' => false, 'message' => $rxError, 'needs_prescription' => $rxLevel], 422);
            }
        }

        // Boleta o factura con facturación electrónica activa: queda pendiente de informar a SUNAT
        $sunatStatus = in_array((int) $request->voucher_type, [1, 2], true) && $this->sunatEnabled($employee->company_id)
            ? Order::SUNAT_PENDING
            : Order::SUNAT_NOT_APPLICABLE;

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
            $stocks = $this->lockStock($employee->branch_id, array_column($lines, 'code'));

            $batches = app(BatchService::class);

            if ($faltante = $this->insufficientStock($lines, $stocks, $batches->expiredMap($employee->branch_id, array_column($lines, 'code')))) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente (o lote vencido) para: ' . $faltante,
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
                'client_id'         => $creditClient?->id ?? $this->validClientId($request, $employee->company_id),
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
                'discount_percent'  => $discountPercent,
                'discount_amount'   => $tax['discount'],
                'rounding_amount'   => $this->cashRounding($tax['total'], (int) $request->payment_type),
                'amount_received'   => $amountReceived,
                'total'             => $tax['total'],
                'credit_balance'    => $creditClient ? $tax['total'] : 0,
                'credit_due_date'   => $creditClient ? ($request->credit_due_date ?: now()->addDays(30)->toDateString()) : null,
                'status'            => 1,
                'sunat_status'      => $sunatStatus,
            ]);

            if ($movedAt) {
                $order->created_at = $movedAt;
                $order->updated_at = $movedAt;
            }

            $order->save();

            if ($prescriptionData) {
                Prescription::create($prescriptionData + [
                    'company_id'  => $employee->company_id,
                    'branch_id'   => $employee->branch_id,
                    'order_id'    => $order->id,
                    'employee_id' => $employee->id,
                ]);
            }

            // Registrar ítems, descontar stock (en unidades base), kardex y lotes
            foreach ($lines as $i => $line) {
                $this->saveLine($order, $line, $tax['lines'][$i], $employee, 'order', $batches, $movedAt);
            }

            DB::commit();

            if ($discountPercent > 0) {
                \App\Models\AuditLog::record('sale.discount', 'Aplicó ' . ($discountAmount ? 'un descuento de S/ ' . number_format($discountAmount, 2) . ' (' . $discountPercent . '%)' : $discountPercent . '% de descuento (S/ ' . number_format($tax['discount'], 2) . ')') . ' en ' . $order->voucher_number, $order->voucher_number, ['total' => $tax['total']]);
            }
            if ($creditClient) {
                \App\Models\AuditLog::record('sale.credit', 'Vendió a crédito S/ ' . number_format($tax['total'], 2) . ' a ' . $creditClient->name, $order->voucher_number, ['vence' => $order->credit_due_date?->toDateString()]);
            }

            // Se informa a SUNAT después de responder al cajero (no lo hace esperar)
            if ($sunatStatus === Order::SUNAT_PENDING) {
                SendOrderToSunat::dispatchAfterResponse($order->id);
            }

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
        abort_if($order->sunat_status === Order::SUNAT_ACCEPTED, 403,
            'Este comprobante ya fue aceptado por SUNAT y no se puede editar. Emite una nota de crédito.');

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

        $products = $this->withSellableStock($products, $branchId);

        $documentTypes = DocumentType::activos()->get();

        // Configuración de impresión de la sede (la vista del POS la necesita)
        $branch      = $employee->branch()->with('config')->first();
        $printConfig = array_merge([
            'default_template' => 'ticket_80mm',
            'auto_print'       => false,
        ], $branch->getSettingGroup('printing'));

        // Ítems actuales de la orden para precargar el carrito. El stock disponible de cada
        // producto (en unidades base) es el stock actual de la sede más lo que la orden ya
        // descontó de ese producto en todas sus líneas (unidad, blíster, caja...).
        $stocks = BranchStock::where('branch_id', $branchId)
            ->whereIn('product_code', $order->items->pluck('product_code'))
            ->pluck('stock_actual', 'product_code');

        $alreadyTaken = $order->items->groupBy('product_code')->map(fn ($items) => $items->sum('base_quantity'));

        // Afectación y nivel de receta actuales de cada producto (el total se recalcula al guardar)
        $orderProducts = Product::whereIn('code', $order->items->pluck('product_code'))
            ->get(['code', 'igv_affectation', 'requires_recipe', 'controlled_type'])
            ->keyBy('code');

        $editItems = $order->items->map(fn ($item) => [
            'code'            => $item->product_code,
            'name'            => $item->product_name,
            'price'           => (float) $item->unit_price,
            'qty'             => (int) $item->quantity,
            'presentation_id' => $item->presentation_id,
            'factor'          => (float) ($item->units_per_item ?: 1),
            'stock'           => (int) (($stocks[$item->product_code] ?? 0) + ($alreadyTaken[$item->product_code] ?? 0)),
            'afectacion'      => $orderProducts[$item->product_code]->igv_affectation ?? '20',
            'receta'          => $orderProducts[$item->product_code]->recipe_level ?? 0,
        ])->values();

        return view('employee.pages.orders.index', [
            'products'       => $products,
            'documentTypes'  => $documentTypes,
            'printConfig'    => $printConfig,
            'historicalCaja' => $cashRegister,
            'editOrder'      => $order,
            'editItems'      => $editItems,
            'publicClient'   => Client::publicFor($employee->company_id),
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
            'items.*.presentation_id' => 'nullable|integer',
            'items.*.price'     => 'required|numeric|min:0',
            'items.*.name'      => 'required|string',
            'payment_type'      => 'required|in:1,2,3,4',
            'subtotal'          => 'nullable|numeric|min:0',
            'igv'               => 'nullable|numeric|min:0',
            'total'             => 'nullable|numeric|min:0',
            'discount_percent'  => 'nullable|integer|min:0|max:100',
            'discount_value'    => 'nullable|numeric|min:0|max:99999999',
            'amount_received'   => 'nullable|numeric|min:0|max:99999999',
        ]);

        $employee     = auth()->guard('employee')->user();
        $cashRegister = $order->cashRegister;

        abort_if($order->company_id !== $employee->company_id, 403);
        abort_if(!$cashRegister || !$cashRegister->isEditable(), 403);
        abort_if($cashRegister->employee_id !== $employee->id, 403);
        abort_if($order->sunat_status === Order::SUNAT_ACCEPTED, 403,
            'Este comprobante ya fue aceptado por SUNAT y no se puede editar. Emite una nota de crédito.');

        // Líneas con precio, presentación y unidades base tomados de la base de datos
        $resolved = $this->resolveLines($request->items, $employee->company_id);

        if (isset($resolved['error'])) {
            return response()->json(['success' => false, 'message' => $resolved['error']], 422);
        }

        $lines = $resolved['lines'];

        [$discountPercent, $discountAmount, $discountError] = $this->discountInput($request, $lines);

        if ($discountError) {
            return response()->json(['success' => false, 'message' => $discountError], 422);
        }

        // Las ventas a crédito no se editan (su deuda y abonos dependen del total)
        if ((int) $order->payment_type === \App\Models\CashRegister::PAYMENT_CREDIT) {
            return response()->json(['success' => false, 'message' => 'Las ventas a crédito no se pueden editar. Anúlalas con una nota de crédito y regístralas de nuevo.'], 422);
        }

        // IGV y totales: se recalculan con la afectación real de cada producto
        $pricing = $this->pricing($lines, $employee->company_id, $request->total, $discountPercent, $discountAmount);

        if (isset($pricing['error'])) {
            return response()->json(['success' => false, 'message' => $pricing['error']], 422);
        }

        $tax = $pricing['tax'];

        // Efectivo recibido (solo pago en efectivo): debe cubrir lo que se cobra
        [$amountReceived, $receivedError] = $this->cashReceived($request, $tax['total']);

        if ($receivedError) {
            return response()->json(['success' => false, 'message' => $receivedError], 422);
        }

        // Si la venta editada incluye productos con receta, ya debe tener su receta registrada
        if ($this->recipeLevel($lines, $employee->company_id) > 0 && !$order->prescription()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta venta incluye productos con receta pero no tiene receta registrada. Regístrala de nuevo desde el punto de venta con los datos de la receta.',
            ], 422);
        }

        // Si al editar la boleta supera S/ 700, el cliente que ya tiene la orden debe estar identificado
        if ($order->isBoleta()) {
            $error = $this->boletaIdentificationError(
                $tax['total'], $order->customer_name, $order->document_type_id, $order->customer_document
            );

            if ($error) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }
        }

        // Boleta o factura aún no aceptada por SUNAT: tras editarla vuelve a quedar pendiente
        $resendSunat = $order->isSunatVoucher() && $this->sunatEnabled($employee->company_id);

        DB::beginTransaction();
        try {
            // 0. Bloquear de una vez el stock de los ítems actuales y nuevos, en orden por código
            $stocks = $this->lockStock($employee->branch_id, array_merge(
                $order->items->pluck('product_code')->all(),
                array_column($lines, 'code')
            ));

            // 1. Devolver stock de los ítems actuales de la orden, en unidades base
            //    (y sus unidades a los lotes de origen)
            $batches = app(BatchService::class);
            $batches->restore('order', $order->id);

            foreach ($order->items as $oldItem) {
                $branchStock = BranchStock::where('branch_id', $employee->branch_id)
                    ->where('product_code', $oldItem->product_code)
                    ->lockForUpdate()
                    ->first();

                if ($branchStock) {
                    $stockRestored = $branchStock->stock_actual + $oldItem->base_quantity;
                    $branchStock->update(['stock_actual' => $stockRestored]);

                    StockMovement::create([
                        'company_id'     => $employee->company_id,
                        'branch_id'      => $employee->branch_id,
                        'product_code'   => $oldItem->product_code,
                        'type'           => 'entrada',
                        'reference_type' => 'order_edit_reversal',
                        'reference_id'   => $order->id,
                        'quantity'       => (int) round($oldItem->base_quantity),
                        'unit_cost'      => round((float) $oldItem->unit_price / ((float) $oldItem->units_per_item ?: 1), 2),
                        'balance'        => (int) $stockRestored,
                    ]);
                }
            }

            // 2. Verificar stock suficiente para los nuevos ítems (con el stock ya devuelto)
            $stocks = BranchStock::where('branch_id', $employee->branch_id)
                ->whereIn('product_code', array_column($lines, 'code'))
                ->get()
                ->keyBy('product_code');

            if ($faltante = $this->insufficientStock($lines, $stocks, $batches->expiredMap($employee->branch_id, array_column($lines, 'code')))) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente (o lote vencido) para: ' . $faltante,
                ], 422);
            }

            // 3. Eliminar ítems anteriores y crear los nuevos
            $order->items()->delete();

            foreach ($lines as $i => $line) {
                $this->saveLine($order, $line, $tax['lines'][$i], $employee, 'order_edit', $batches);
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
                'discount_percent'  => $discountPercent,
                'discount_amount'   => $tax['discount'],
                'rounding_amount'   => $this->cashRounding($tax['total'], (int) $request->payment_type),
                'amount_received'   => $amountReceived,
                'total'             => $tax['total'],
            ] + ($resendSunat ? [
                'sunat_status'  => Order::SUNAT_PENDING,
                'sunat_code'    => null,
                'sunat_message' => null,
            ] : []));

            DB::commit();

            if ($resendSunat) {
                SendOrderToSunat::dispatchAfterResponse($order->id);
            }

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
