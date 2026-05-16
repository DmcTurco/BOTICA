<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de historial de empleados.
     * Registra cada cambio de sede o rol: ingresos, transferencias y ascensos.
     * La tabla employees guarda el estado actual; esta tabla guarda la línea de tiempo completa.
     */
    public function up(): void
    {
        Schema::create('employee_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index();            // empleado al que pertenece el historial
            $table->unsignedBigInteger('branch_id')->index();              // sede en la que estuvo/está
            $table->unsignedBigInteger('role_id')->index();                // rol que tenía en ese período
            $table->timestamp('started_at');                               // inicio del período
            $table->timestamp('ended_at')->nullable();                     // fin del período (null = estado actual)
            $table->string('reason', 100)->nullable()
                  ->comment('ingreso, transferencia, promocion, ajuste');  // motivo del cambio
            $table->unsignedBigInteger('authorized_by')->nullable()->index(); // employee_id del que aprobó
            $table->text('notes')->nullable();                             // observaciones libres
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de historial de empleados.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_histories');
    }
};
