<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de detalle de compras (líneas de cada compra).
     */
    public function up(): void
    {
        Schema::create('purchase_detail', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_id')->index();
            $table->string('product_code', 20)->index();

            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2)->comment('Precio de compra unitario al momento de la compra');
            $table->decimal('subtotal', 10, 2);

            $table->date('expiration_date')->nullable()->comment('Vencimiento del lote ingresado');
            $table->string('batch', 30)->nullable()->comment('Número de lote');

            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de detalle de compras.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_detail');
    }
};
