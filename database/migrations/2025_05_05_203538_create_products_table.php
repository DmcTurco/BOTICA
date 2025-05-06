<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->primary();
            $table->string('came', 150);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('laboratory_id')->nullable();
            $table->string('active_ingredient', 100)->nullable();
            
            // Datos de compra
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('package_purchase_price', 10, 2)->nullable();
            $table->integer('units_per_package')->nullable()->comment('Cuántas unidades vienen en un paquete/blister/caja');
            
            // Datos de venta
            $table->decimal('unit_sale_price', 10, 2);
            $table->decimal('package_sale_price', 10, 2)->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            
            // Stock
            $table->decimal('stock_actual', 10, 2);
            $table->integer('stock_minimum')->nullable();
            $table->integer('stock_maximum')->nullable();
            
            // Control
            $table->boolean('taxed_product')->default(false)->comment('Si aplica IGV');
            $table->boolean('requires_recipe')->default(false);
            $table->date('expiration_date')->nullable();
            $table->string('location', 50)->nullable()->comment('Ubicación física en la farmacia');
            $table->smallInteger('status')->nullable();
            
            // Control de sistema
            $table->timestamps();
            $table->softDeletes(); // Permite "eliminar" sin borrar realmente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
