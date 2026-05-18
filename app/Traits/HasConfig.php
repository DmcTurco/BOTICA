<?php

namespace App\Traits;

use App\Models\Config;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Agrega soporte de configuración JSON polimórfica al modelo que lo use.
 *
 * Uso básico:
 *   $branch->getSetting('printing', 'auto_print', false)
 *   $branch->setSetting('printing', 'auto_print', true)
 *   $branch->getSettingGroup('printing')
 *   $branch->setSettingGroup('printing', [...])
 */
trait HasConfig
{
    // ── Relación ────────────────────────────────────────────────

    /**
     * Fila de configuración de esta entidad.
     */
    public function config(): MorphOne
    {
        return $this->morphOne(Config::class, 'configurable');
    }

    // ── Lectura ─────────────────────────────────────────────────

    /**
     * Lee un valor puntual dentro de un grupo.
     *
     * Ejemplo: $branch->getSetting('printing', 'auto_print', false)
     */
    public function getSetting(string $group, string $key, mixed $default = null): mixed
    {
        $setting = $this->config?->setting ?? [];

        return $setting[$group][$key] ?? $default;
    }

    /**
     * Lee todo el grupo de configuración como array.
     *
     * Ejemplo: $branch->getSettingGroup('printing')
     */
    public function getSettingGroup(string $group, array $default = []): array
    {
        $setting = $this->config?->setting ?? [];

        return $setting[$group] ?? $default;
    }

    // ── Escritura ───────────────────────────────────────────────

    /**
     * Actualiza un valor puntual dentro de un grupo.
     * Crea la fila de config si no existe todavía.
     *
     * Ejemplo: $branch->setSetting('printing', 'auto_print', true)
     */
    public function setSetting(string $group, string $key, mixed $value): void
    {
        $config  = $this->config ?? $this->config()->create(['setting' => []]);
        $setting = $config->setting ?? [];

        $setting[$group][$key] = $value;

        $config->update(['setting' => $setting]);

        // Refrescar relación en memoria
        $this->setRelation('config', $config->fresh());
    }

    /**
     * Reemplaza todo un grupo de configuración de una vez.
     * Útil al guardar formularios completos.
     *
     * Ejemplo: $branch->setSettingGroup('printing', ['auto_print' => true, 'printers' => [...]])
     */
    public function setSettingGroup(string $group, array $values): void
    {
        $config  = $this->config ?? $this->config()->create(['setting' => []]);
        $setting = $config->setting ?? [];

        $setting[$group] = $values;

        $config->update(['setting' => $setting]);

        $this->setRelation('config', $config->fresh());
    }

    /**
     * Elimina un grupo completo de configuración.
     *
     * Ejemplo: $branch->forgetSettingGroup('printing')
     */
    public function forgetSettingGroup(string $group): void
    {
        if (! $this->config) {
            return;
        }

        $setting = $this->config->setting ?? [];
        unset($setting[$group]);

        $this->config->update(['setting' => $setting]);
        $this->setRelation('config', $this->config->fresh());
    }
}
