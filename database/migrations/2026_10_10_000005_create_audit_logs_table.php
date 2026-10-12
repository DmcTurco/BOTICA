<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la bitácora de acciones sensibles: quién hizo qué, sobre qué y cuándo
     * (anulaciones, ajustes, cambios de precio, descuentos, privilegios, cobros...).
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('employee_id')->nullable()->index();   // quien hizo la acción
            $table->string('employee_name', 100)->nullable();                 // se guarda el nombre por si luego eliminan al empleado

            $table->string('action', 40)->index();                            // ej. purchase.void, price.update
            $table->string('subject', 60)->nullable();                        // sobre qué (ej. "Compra #12")
            $table->string('description', 255);
            $table->json('details')->nullable();
            $table->string('ip', 45)->nullable();

            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Elimina la bitácora.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
