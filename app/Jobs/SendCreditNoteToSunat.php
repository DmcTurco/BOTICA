<?php

namespace App\Jobs;

use App\Models\CompanySunatSetting;
use App\Models\CreditNote;
use App\Models\Order;
use App\Services\Sunat\Contracts\SunatGateway;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Envía una nota de crédito a SUNAT y guarda el resultado en la nota
 * (estado, código, mensaje, hash, XML firmado y CDR).
 * Igual que SendOrderToSunat: se despacha después de responder y se puede reenviar si falla.
 */
class SendCreditNoteToSunat
{
    use Dispatchable;

    public function __construct(public int $creditNoteId)
    {
    }

    public function handle(SunatGateway $gateway): ?CreditNote
    {
        // Un solo envío a la vez por nota (evita duplicados por doble clic o reintentos)
        $lock = Cache::lock("sunat-credit-note-{$this->creditNoteId}", 120);

        if (!$lock->get()) {
            return null;
        }

        try {
            $note = CreditNote::find($this->creditNoteId);

            if (!$note || !$note->canResendToSunat()) {
                return $note;
            }

            $result = $gateway->sendCreditNote($note);

            // SUNAT a veces corta envíos muy seguidos con un error HTTP: se reintenta una vez
            if ($result->status === Order::SUNAT_ERROR && $result->code === 'HTTP') {
                sleep(2);
                $result = $gateway->sendCreditNote($note);
            }

            $setting = CompanySunatSetting::where('company_id', $note->company_id)->first();

            $data = [
                'sunat_status'      => $result->status,
                'sunat_code'        => $result->code,
                'sunat_message'     => $result->message,
                'sunat_hash'        => $result->hash ?? $note->sunat_hash,
                'sunat_environment' => $setting?->environment,
                'sunat_attempts'    => $note->sunat_attempts + 1,
                'sunat_sent_at'     => now(),
            ];

            // Archivos en el disco privado: sunat/{compañía}/{serie-número}.xml y -CDR.zip
            $base = "sunat/{$note->company_id}/{$note->voucher_number}";

            if ($result->xml) {
                Storage::disk('local')->put("{$base}.xml", $result->xml);
                $data['sunat_xml_path'] = "{$base}.xml";
            }

            if ($result->cdrZip) {
                Storage::disk('local')->put("{$base}-CDR.zip", $result->cdrZip);
                $data['sunat_cdr_path'] = "{$base}-CDR.zip";
            }

            $note->update($data);

            return $note;
        } finally {
            $lock->release();
        }
    }
}
