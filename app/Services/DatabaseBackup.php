<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Respaldo de la base de datos: PostgreSQL con pg_dump (formato comprimido) o SQLite copiando el archivo.
 */
class DatabaseBackup
{
    /**
     * Genera el respaldo y devuelve la ruta del archivo creado.
     *
     * @throws RuntimeException si el motor no es compatible o el respaldo falla
     */
    public function run(): string
    {
        $config = config('database.connections.' . config('database.default'));
        $dir    = config('backup.path');

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("No se pudo crear la carpeta de respaldos: {$dir}");
        }

        $stamp = now()->format('Ymd-His');

        return match ($config['driver']) {
            'pgsql'  => $this->dumpPostgres($config, "{$dir}/botica-{$stamp}.dump"),
            'sqlite' => $this->copySqlite($config, "{$dir}/botica-{$stamp}.sqlite"),
            default  => throw new RuntimeException('Respaldo no disponible para el motor «' . $config['driver'] . '».'),
        };
    }

    /**
     * Comando de pg_dump para una conexión (público para poder verificarlo sin ejecutarlo).
     *
     * @return array<int, string>
     */
    public function postgresCommand(array $config, string $file): array
    {
        return [
            config('backup.pg_dump'), '--host', (string) $config['host'], '--port', (string) $config['port'],
            '--username', (string) $config['username'], '--format', 'custom', '--no-owner', '--file', $file, (string) $config['database'],
        ];
    }

    /**
     * Borra los respaldos más antiguos que `keep_days`. Devuelve cuántos eliminó.
     */
    public function prune(): int
    {
        $limit   = now()->subDays(config('backup.keep_days'))->getTimestamp();
        $deleted = 0;

        foreach (glob(config('backup.path') . '/botica-*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $limit && @unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function dumpPostgres(array $config, string $file): string
    {
        $process = new Process($this->postgresCommand($config, $file), null, ['PGPASSWORD' => (string) $config['password']]);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful() || !is_file($file) || filesize($file) === 0) {
            @unlink($file);
            throw new RuntimeException('pg_dump falló: ' . trim($process->getErrorOutput() ?: 'sin detalle') . ' (revisa PG_DUMP_PATH en .env)');
        }

        return $file;
    }

    private function copySqlite(array $config, string $file): string
    {
        $source = $config['database'];

        if ($source === ':memory:' || !is_file($source)) {
            throw new RuntimeException('La base SQLite no es un archivo: no se puede respaldar.');
        }

        if (!copy($source, $file)) {
            throw new RuntimeException('No se pudo copiar la base SQLite.');
        }

        return $file;
    }
}
