<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea los abonos de clientes a sus ventas a crédito (fiado).
     * Si el abono es en efectivo queda ligado a la caja que lo recibió, para el cuadre.
     */
    public function up(): void
    {
        Schema::create('credit_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('order_id')->index();           // venta a crédito que se abona
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('cash_register_id')->nullable()->index(); // caja que recibió el efectivo
            $table->unsignedBigInteger('employee_id')->index();        // quién cobró

            $table->decimal('amount', 10, 2);
            $table->unsignedTinyInteger('method')->default(1)->comment('1=efectivo,2=tarjeta,3=transferencia,4=yape');
            $table->string('reference', 50)->nullable();
            $table->date('paid_at');
            $table->uuid('batch')->nullable()->comment('Agrupa los abonos de un mismo cobro repartido en varias ventas');

            $table->timestamps();
        });
    }

    /**
     * Elimina los abonos de clientes.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_payments');
    }
};
