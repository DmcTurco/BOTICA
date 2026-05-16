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
            $table->string('code', 20)->primary();                    // PK del producto (código único)
            $table->unsignedBigInteger('company_id')->index();        // compañía dueña del catálogo
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('laboratory_id')->nullable()->index();
            $table->string('active_ingredient', 100)->nullable();

            // Datos de compra (precio de referencia del catálogo)
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('package_purchase_price', 10, 2)->nullable();
            $table->integer('units_per_package')->nullable()->comment('Cuántas unidades vienen en un paquete/blister/caja');

            // Datos de venta
            $table->decimal('unit_sale_price', 10, 2);
            $table->decimal('package_sale_price', 10, 2)->nullable();
            $table->unsignedBigInteger('unit_id')->nullable()->index();

            // Stock eliminado del catálogo — ahora vive en branch_stock (por sede)

            // Control
            $table->boolean('taxed_product')->default(false)->comment('Si aplica IGV');
            $table->boolean('requires_recipe')->default(false);
            $table->string('location', 50)->nullable()->comment('Ubicación de referencia en farmacia');
            $table->smallInteger('status')->nullable();

            $table->unsignedBigInteger('employee_id')->index();       // empleado que registró el producto

            // Control de sistema
            $table->timestamps();
            $table->softDeletes();
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
