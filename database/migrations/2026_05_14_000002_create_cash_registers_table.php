<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de cajas registradoras.
     */
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();        // compañía a la que pertenece la caja
            $table->unsignedBigInteger('branch_id')->index();         // sede donde está la caja
            $table->unsignedBigInteger('employee_id')->index();       // empleado responsable de la caja
            $table->decimal('opening_amount', 10, 2)->default(0);   // monto declarado al abrir
            $table->json('opening_denominations')->nullable();      // desglose de billetes/monedas al abrir
            $table->decimal('closing_amount', 10, 2)->nullable();   // monto contado al cerrar
            $table->json('closing_denominations')->nullable();      // desglose de billetes/monedas al cerrar
            $table->decimal('expected_amount', 10, 2)->nullable();  // suma de órdenes del turno
            $table->decimal('difference', 10, 2)->nullable();       // closing - expected
            $table->unsignedTinyInteger('status')->default(1)->comment('1=abierta, 0=cerrada');
            $table->date('register_date');                          // fecha a la que corresponde la caja (puede ser pasada)
            $table->unsignedTinyInteger('approval_status')->default(0)->comment('0=normal(hoy), 1=pendiente, 2=aprobada, 3=rechazada');
            $table->unsignedBigInteger('approved_by')->nullable()->index(); // branch_admin que aprobó/rechazó
            $table->timestamp('approved_at')->nullable();           // cuándo fue aprobada/rechazada
            $table->text('rejection_reason')->nullable();           // motivo del rechazo
            $table->text('notes')->nullable();                       // observaciones al cierre
            $table->timestamp('opened_at');                         // cuándo se registró en el sistema
            $table->timestamp('closed_at')->nullable();             // cuándo se cerró
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de cajas registradoras.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
