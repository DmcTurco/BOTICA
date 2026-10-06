<?php

namespace App\Services\Sunat;

use App\Models\CompanySunatSetting;
use App\Models\Order;
use App\Models\SunatSummary;
use App\Services\Sunat\Contracts\SunatGateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Informa a SUNAT las boletas de una compañía agrupadas en resúmenes diarios.
 * Solo aplica a compañías con boleta_mode = summary; las facturas siempre van individuales.
 */
class DailySummaryService
{
    /** Máximo de boletas por resumen que acepta SUNAT */
    private const MAX_LINES = 500;

    public function __construct(private SunatGateway $gateway)
    {
    }

    /**
     * Envía las boletas pendientes (o con error) de la compañía, un resumen por fecha de emisión.
     * Con $date solo se incluyen las boletas de ese día; sin ella, las de todos los días hasta hoy.
     * Devuelve los resúmenes generados (vacío si no había boletas o la compañía no usa resúmenes).
     *
     * @return Collection<int, SunatSummary>
     */
    public function sendPending(int $companyId, ?Carbon $date = null): Collection
    {
        $setting = CompanySunatSetting::where('company_id', $companyId)->first();

        if (!$setting || !$setting->enabled || !$setting->isReady() || !$setting->usesBoletaSummary()) {
            return collect();
        }

        // Un solo envío de resúmenes a la vez por compañía (evita correlativos duplicados)
        $lock = Cache::lock("sunat-summary-{$companyId}", 300);

        if (!$lock->get()) {
            return collect();
        }

        try {
            $query = Order::where('company_id', $companyId)
                ->where('voucher_type', 1)
                ->where('status', 1)
                ->whereIn('sunat_status', [Order::SUNAT_PENDING, Order::SUNAT_ERROR])
                ->whereNull('sunat_summary_id')
                ->orderBy('created_at')
                ->orderBy('id');

            $date
                ? $query->whereDate('created_at', $date->toDateString())
                : $query->whereDate('created_at', '<=', now()->toDateString());

            $summaries = collect();

            // Un resumen por fecha de emisión y por cada bloque de 500 boletas
            $query->get()
                ->groupBy(fn (Order $order) => $order->created_at->toDateString())
                ->each(function (Collection $day, string $referenceDate) use ($companyId, $setting, $summaries) {
                    foreach ($day->chunk(self::MAX_LINES) as $chunk) {
                        $summaries->push($this->sendChunk($companyId, $referenceDate, $chunk->values(), $setting));
                    }
                });

            return $summaries;
        } finally {
            $lock->release();
        }
    }

    /**
     * Consulta en SUNAT los resúmenes recibidos que aún no tienen veredicto.
     * Devuelve cuántos quedaron resueltos (aceptados o rechazados).
     */
    public function refreshProcessing(int $companyId): int
    {
        $resolved = 0;

        SunatSummary::where('company_id', $companyId)
            ->where('status', SunatSummary::STATUS_PROCESSING)
            ->whereNotNull('ticket')
            ->get()
            ->each(function (SunatSummary $summary) use (&$resolved) {
                $this->refresh($summary);

                if (in_array($summary->status, [SunatSummary::STATUS_ACCEPTED, SunatSummary::STATUS_REJECTED], true)) {
                    $resolved++;
                }
            });

        return $resolved;
    }

    /**
     * Crea el resumen de un bloque de boletas, lo envía y consulta su resultado una vez.
     */
    private function sendChunk(int $companyId, string $referenceDate, Collection $orders, CompanySunatSetting $setting): SunatSummary
    {
        $summary = DB::transaction(function () use ($companyId, $referenceDate, $orders, $setting) {
            $today       = now()->toDateString();
            $correlative = (int) SunatSummary::where('company_id', $companyId)
                ->whereDate('generation_date', $today)
                ->max('correlative') + 1;

            $summary = SunatSummary::create([
                'company_id'      => $companyId,
                'reference_date'  => $referenceDate,
                'generation_date' => $today,
                'correlative'     => $correlative,
                'identifier'      => 'RC-' . now()->format('Ymd') . '-' . $correlative,
                'documents_count' => $orders->count(),
                'status'          => SunatSummary::STATUS_PENDING,
                'environment'     => $setting->environment,
            ]);

            Order::whereIn('id', $orders->pluck('id'))->update(['sunat_summary_id' => $summary->id]);

            return $summary;
        });

        $result = $this->gateway->sendSummary($summary, $orders);

        $data = [
            'code'    => $result->code,
            'message' => $result->message,
            'sent_at' => now(),
        ];

        if ($result->xml) {
            $data['xml_path'] = "sunat/{$companyId}/{$summary->identifier}.xml";
            Storage::disk('local')->put($data['xml_path'], $result->xml);
        }

        if ($result->status === Order::SUNAT_PENDING) {
            // SUNAT lo recibió: queda en proceso hasta consultar el ticket
            $summary->update($data + ['status' => SunatSummary::STATUS_PROCESSING, 'ticket' => $result->ticket]);
            $this->refresh($summary);

            return $summary;
        }

        if ($result->status === Order::SUNAT_REJECTED) {
            $summary->update($data + ['status' => SunatSummary::STATUS_REJECTED]);
            $this->markOrders($summary, Order::SUNAT_REJECTED, $result->code, $result->message);

            return $summary;
        }

        // Falla de conexión o de envío: se liberan las boletas para incluirlas en el próximo resumen
        $summary->update($data + ['status' => SunatSummary::STATUS_ERROR]);
        Order::where('sunat_summary_id', $summary->id)->update(['sunat_summary_id' => null]);

        return $summary;
    }

    /**
     * Consulta el ticket del resumen y, si SUNAT ya respondió, actualiza el resumen y sus boletas.
     */
    private function refresh(SunatSummary $summary): void
    {
        $result = $this->gateway->summaryStatus($summary);

        // Sigue procesándose, o la consulta falló: se conserva el ticket para volver a consultar
        if ($result->status === Order::SUNAT_PENDING || $result->status === Order::SUNAT_ERROR) {
            $summary->update(['code' => $result->code, 'message' => $result->message]);

            return;
        }

        $data = [
            'status'  => $result->status === Order::SUNAT_ACCEPTED ? SunatSummary::STATUS_ACCEPTED : SunatSummary::STATUS_REJECTED,
            'code'    => $result->code,
            'message' => $result->message,
        ];

        if ($result->cdrZip) {
            $data['cdr_path'] = "sunat/{$summary->company_id}/{$summary->identifier}-CDR.zip";
            Storage::disk('local')->put($data['cdr_path'], $result->cdrZip);
        }

        $summary->update($data);
        $this->markOrders($summary, $result->status, $result->code, $result->message);

        // Boletas aceptadas: se guarda el hash propio de cada una para el QR del ticket
        if ($result->status === Order::SUNAT_ACCEPTED) {
            $orders = Order::where('sunat_summary_id', $summary->id)->whereNull('sunat_hash')->get();

            foreach ($this->gateway->digestOrders($orders) as $orderId => $hash) {
                Order::whereKey($orderId)->update(['sunat_hash' => $hash]);
            }
        }
    }

    /**
     * Copia el veredicto del resumen a cada boleta que incluye.
     */
    private function markOrders(SunatSummary $summary, string $status, ?string $code, ?string $message): void
    {
        Order::where('sunat_summary_id', $summary->id)->update([
            'sunat_status'      => $status,
            'sunat_code'        => $code,
            'sunat_message'     => $message,
            'sunat_environment' => $summary->environment,
            'sunat_attempts'    => DB::raw('sunat_attempts + 1'),
            'sunat_sent_at'     => now(),
        ]);
    }
}
