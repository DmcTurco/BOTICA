<?php

namespace App\Jobs;

use App\Models\CompanySunatSetting;
use App\Models\Order;
use App\Services\Sunat\Contracts\SunatGateway;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Envía una boleta o factura a SUNAT y guarda el resultado en la orden
 * (estado, código, mensaje, hash, XML firmado y CDR).
 *
 * Se despacha con dispatchAfterResponse(): el cajero recibe su respuesta primero y
 * el envío ocurre después, sin necesitar un worker de colas. Si falla, la orden queda
 * como "error" (o "rejected") y se puede reenviar desde el historial.
 */
class SendOrderToSunat
{
    use Dispatchable;

    public function __construct(public int $orderId)
    {
    }

    public function handle(SunatGateway $gateway): ?Order
    {
        // Un solo envío a la vez por orden (evita duplicados por doble clic o reintentos)
        $lock = Cache::lock("sunat-order-{$this->orderId}", 120);

        if (!$lock->get()) {
            return null;
        }

        try {
            $order = Order::find($this->orderId);

            if (!$order || !$order->isSunatVoucher() || !$order->canResendToSunat()) {
                return $order;
            }

            $result = $gateway->send($order);

            // SUNAT a veces corta envíos muy seguidos con un error HTTP (p. ej. "Unauthorized"):
            // se reintenta una vez tras una pausa corta antes de dejar la orden con error.
            if ($result->status === Order::SUNAT_ERROR && $result->code === 'HTTP') {
                sleep(2);
                $result = $gateway->send($order);
            }

            $setting = CompanySunatSetting::where('company_id', $order->company_id)->first();

            $data = [
                'sunat_status'      => $result->status,
                'sunat_code'        => $result->code,
                'sunat_message'     => $result->message,
                'sunat_hash'        => $result->hash ?? $order->sunat_hash,
                'sunat_environment' => $setting?->environment,
                'sunat_attempts'    => $order->sunat_attempts + 1,
                'sunat_sent_at'     => now(),
            ];

            // Archivos en el disco privado: sunat/{compañía}/{serie-número}.xml y R-{serie-número}.zip
            $base = "sunat/{$order->company_id}/{$order->voucher_number}";

            if ($result->xml) {
                Storage::disk('local')->put("{$base}.xml", $result->xml);
                $data['sunat_xml_path'] = "{$base}.xml";
            }

            if ($result->cdrZip) {
                Storage::disk('local')->put("{$base}-CDR.zip", $result->cdrZip);
                $data['sunat_cdr_path'] = "{$base}-CDR.zip";
            }

            $order->update($data);

            return $order;
        } finally {
            $lock->release();
        }
    }
}
