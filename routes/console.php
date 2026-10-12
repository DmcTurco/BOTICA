<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Resumen diario de boletas para las compañías que lo usan: envía al cierre del día
// y vuelve en la mañana para consultar los resúmenes en proceso y reintentar los fallidos.
// Requiere el cron de Laravel en el servidor: * * * * * php artisan schedule:run
Schedule::command('sunat:send-summaries')->dailyAt('23:30');
Schedule::command('sunat:send-summaries')->dailyAt('06:00');

// Reintenta cada hora las facturas y boletas individuales que quedaron pendientes o con error de envío
Schedule::command('sunat:retry-pending')->hourly();

// Respaldo diario de la base de datos (se conservan BACKUP_KEEP_DAYS días)
Schedule::command('db:backup')->dailyAt('02:00');
