<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de configuración polimórfica.
     * Una fila por entidad (Branch, Company, etc.) — cada una guarda
     * toda su configuración en una columna JSON organizada por grupos.
     *
     * Ejemplo de payload:
     * {
     *   "printing": { "default_template": "ticket_80mm", "auto_print": true, "printers": [...] },
     *   "inventory": { "low_stock_threshold": 10 },
     *   "notifications": { ... }
     * }
     */
    public function up(): void
    {
        Schema::create('configs', function (Blueprint $table) {
            $table->id();

            // Relación polimórfica (sin FK constraint, según convención del proyecto)
            $table->string('configurable_type');
            $table->unsignedBigInteger('configurable_id')->index();

            // Toda la configuración de esta entidad en JSON
            $table->jsonb('setting')->default('{}');

            $table->timestamps();

            // Una sola fila de config por entidad
            $table->unique(['configurable_type', 'configurable_id'], 'configs_entity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configs');
    }
};
