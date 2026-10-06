<?php

namespace App\Console\Commands;

use App\Jobs\SendOrderToSunat;
use App\Models\CompanySunatSetting;
use App\Models\Order;
use App\Services\Sunat\Contracts\SunatGateway;
use Illuminate\Console\Command;

class RetrySunatPending extends Command
{
    protected $signature = 'sunat:retry-pending {--max-attempts=10 : Intentos máximos por comprobante}';

    protected $description = 'Reintenta el envío a SUNAT de facturas y boletas individuales pendientes o con error';

    /**
     * Las boletas de compañías con resumen diario no pasan por aquí: las recoge sunat:send-summaries.
     * Los rechazados tampoco se reintentan (hay que corregirlos); solo pendientes y errores de envío.
     */
    public function handle(SunatGateway $gateway): int
    {
        $settings = CompanySunatSetting::where('enabled', true)->get()->keyBy('company_id');
        $sent     = 0;

        Order::whereIn('company_id', $settings->keys())
            ->whereIn('voucher_type', [1, 2])
            ->where('status', 1)
            ->whereIn('sunat_status', [Order::SUNAT_PENDING, Order::SUNAT_ERROR])
            ->whereNull('sunat_summary_id')
            ->where('sunat_attempts', '<', (int) $this->option('max-attempts'))
            ->orderBy('created_at')
            ->get()
            ->each(function (Order $order) use ($settings, $gateway, &$sent) {
                // Boletas en modo resumen: no se envían una por una
                if ($order->isBoleta() && $settings[$order->company_id]->usesBoletaSummary()) {
                    return;
                }

                // Plazo vencido: se deja para reenvío manual (SUNAT puede rechazarlo)
                if ($order->sunatDaysLeft() < 0) {
                    return;
                }

                (new SendOrderToSunat($order->id))->handle($gateway);
                $sent++;

                // SUNAT beta corta envíos muy seguidos
                sleep(1);
            });

        $this->info("Comprobantes reintentados: {$sent}.");

        return self::SUCCESS;
    }
}
