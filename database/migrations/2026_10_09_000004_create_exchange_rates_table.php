<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de tipo de cambio (USD → PEN) por día.
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->date('rate_date');                                  // día al que corresponde el tipo de cambio
            $table->decimal('buy_rate', 8, 4);                          // compra
            $table->decimal('sell_rate', 8, 4);                         // venta
            $table->unsignedBigInteger('employee_id')->index();         // quién lo registró

            $table->timestamps();

            // Un solo tipo de cambio por compañía y día
            $table->unique(['company_id', 'rate_date']);
        });
    }

    /**
     * Elimina la tabla de tipo de cambio.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
