<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Respaldo de la base de datos
    |--------------------------------------------------------------------------
    | `php artisan db:backup` genera una copia en esta carpeta y borra las que superen los días indicados.
    | Se ejecuta solo cada madrugada si el servidor tiene el cron de Laravel (schedule:run).
    | En PostgreSQL necesita el programa pg_dump: si no está en el PATH, indica su ruta completa en PG_DUMP_PATH.
    */
    'path'      => env('BACKUP_PATH', storage_path('app/backups')),
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),
    'pg_dump'   => env('PG_DUMP_PATH', 'pg_dump'),
];
