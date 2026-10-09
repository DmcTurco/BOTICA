<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la bitácora de cambios de precio: quién cambió qué precio, de cuánto a cuánto y cuándo.
     */
    public function up(): void
    {
        Schema::create('price_changes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->string('product_code', 20)->index();
            $table->string('field', 30)->comment('purchase_price | unit_sale_price');
            $table->decimal('old_value', 10, 2);
            $table->decimal('new_value', 10, 2);
            $table->unsignedBigInteger('employee_id')->index();      // quién hizo el cambio

            $table->timestamps();
        });
    }

    /**
     * Elimina la bitácora de cambios de precio.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_changes');
    }
};
