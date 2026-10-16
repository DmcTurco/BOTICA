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
            $table->string('code', 20)->primary();                    // PK interna, la genera el sistema (P-000001)
            $table->unsignedBigInteger('company_id')->index();        // compañía dueña del catálogo
            $table->string('barcode', 30)->nullable();                // código de barras (EAN) de la caja; opcional
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('laboratory_id')->nullable()->index();
            $table->string('active_ingredient', 100)->nullable();

            // Precios de la unidad base (la más pequeña que se vende: 1 tableta, 1 frasco...).
            // Blíster, caja, etc. son presentaciones (tabla presentations) con su propio precio.
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('unit_sale_price', 10, 2);
            $table->unsignedBigInteger('unit_id')->nullable()->index(); // unidad base

            // Stock eliminado del catálogo — ahora vive en branch_stock (por sede)

            // Control
            $table->boolean('taxed_product')->default(false)->comment('Si aplica IGV');
            $table->string('igv_affectation', 2)->default('20')->comment('Catálogo SUNAT 07: 10 gravado, 20 exonerado, 30 inafecto');
            $table->boolean('requires_recipe')->default(false);
            $table->string('controlled_type', 15)->nullable()->comment('psicotropico | estupefaciente (libro de controlados)');
            $table->string('sanitary_registry', 30)->nullable()->comment('Registro sanitario DIGEMID');
            $table->string('location', 50)->nullable()->comment('Ubicación de referencia en farmacia');
            $table->smallInteger('status')->nullable();

            $table->unsignedBigInteger('employee_id')->index();       // empleado que registró el producto

            // Control de sistema
            $table->timestamps();
            $table->softDeletes();

            // El código de barras no se repite dentro de una compañía, pero dos boticas sí pueden tener el mismo
            $table->unique(['company_id', 'barcode']);
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
