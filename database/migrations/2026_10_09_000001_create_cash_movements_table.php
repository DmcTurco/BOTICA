<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de movimientos de caja: gastos del día y otros ingresos en efectivo
     * que no son ventas. Entran al cálculo del efectivo esperado al cerrar la caja.
     */
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('cash_register_id')->index();   // caja en la que se registró
            $table->unsignedBigInteger('employee_id')->index();        // quién lo registró

            $table->string('type', 10)->comment('income=otro ingreso, expense=gasto');
            $table->string('concept', 150);                            // ej. "Pago de flete", "Sencillo recibido"
            $table->decimal('amount', 10, 2);                          // siempre positivo; el tipo indica si suma o resta
            $table->text('notes')->nullable();

            $table->unsignedTinyInteger('status')->default(1)->comment('1=vigente, 0=anulado');
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de movimientos de caja.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
