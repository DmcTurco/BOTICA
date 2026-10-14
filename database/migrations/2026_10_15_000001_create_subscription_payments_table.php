<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pagos de la mensualidad del servicio que el administrador registra a cada empresa.
     * Cada pago cubre uno o más meses y extiende companies.paid_until.
     */
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();      // empresa que pagó
            $table->unsignedBigInteger('admin_id')->index();        // administrador que registró el pago
            $table->decimal('amount', 10, 2);                       // monto recibido
            $table->unsignedSmallInteger('months');                 // meses que cubre
            $table->date('period_from');                            // primer día cubierto
            $table->date('period_to');                              // último día cubierto (nuevo paid_until)
            $table->date('paid_at');                                // fecha en que pagó
            $table->string('method', 20);                           // yape | plin | transfer | cash | other
            $table->string('operation_number', 50)->nullable();     // N° de operación del Yape / transferencia
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de pagos de la mensualidad.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
