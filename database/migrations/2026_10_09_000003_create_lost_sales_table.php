<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de ventas perdidas: lo que el cliente pidió y no se pudo vender
     * (sin stock o producto que no se maneja). Sirve para decidir qué reponer o incorporar.
     */
    public function up(): void
    {
        Schema::create('lost_sales', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('employee_id')->index();

            $table->string('product_code', 20)->nullable()->index();   // si el producto existe en el catálogo
            $table->string('product_name', 150);                       // nombre pedido (libre si no existe)
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('reason', 20)->default('no_stock')->comment('no_stock | not_carried | price | other');
            $table->string('notes', 255)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de ventas perdidas.
     */
    public function down(): void
    {
        Schema::dropIfExists('lost_sales');
    }
};
