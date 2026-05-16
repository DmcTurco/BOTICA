<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de stock por sede.
     * Un registro por sede/producto: refleja el inventario actual de ese producto en esa sede.
     * Se actualiza automáticamente con cada compra, venta o ajuste (vía stock_movements).
     */
    public function up(): void
    {
        Schema::create('branch_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->index();     // sede a la que pertenece el stock
            $table->string('product_code', 20)->index();          // producto del catálogo
            $table->decimal('stock_actual', 10, 2)->default(0);  // cantidad actual disponible
            $table->integer('stock_minimum')->nullable();         // alerta de stock bajo
            $table->integer('stock_maximum')->nullable();         // límite de reposición

            $table->timestamps();

            // Un solo registro por sede + producto
            $table->unique(['branch_id', 'product_code']);
        });
    }

    /**
     * Elimina la tabla de stock por sede.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_stock');
    }
};
