<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Respalda la base de datos y borra los respaldos antiguos';

    public function handle(DatabaseBackup $backup): int
    {
        try {
            $file = $backup->run();
        } catch (\Throwable $e) {
            Log::error('Falló el respaldo de la base de datos: ' . $e->getMessage());
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $deleted = $backup->prune();

        $this->info('Respaldo creado: ' . $file . ($deleted ? " ({$deleted} antiguo(s) eliminado(s))" : ''));

        return self::SUCCESS;
    }
}
