<?php

namespace App\Console\Commands;

use App\Models\CompanySunatSetting;
use App\Services\Sunat\DailySummaryService;
use Illuminate\Console\Command;

class SendSunatSummaries extends Command
{
    protected $signature = 'sunat:send-summaries';

    protected $description = 'Envía a SUNAT el resumen diario de boletas pendientes y consulta los resúmenes en proceso';

    public function handle(DailySummaryService $service): int
    {
        $companies = CompanySunatSetting::where('enabled', true)
            ->where('boleta_mode', CompanySunatSetting::BOLETA_SUMMARY)
            ->pluck('company_id');

        foreach ($companies as $companyId) {
            $resolved  = $service->refreshProcessing($companyId);
            $summaries = $service->sendPending($companyId);

            $this->info("Compañía {$companyId}: {$summaries->count()} resúmenes enviados, {$resolved} resueltos.");
        }

        return self::SUCCESS;
    }
}
