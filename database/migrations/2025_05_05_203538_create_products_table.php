<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->string('code', 20)->primary();                    // PK interna, la genera el sistema (P-00000001)
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

            $table->unsignedBigInteger('employee_id')->nullable()->index(); // empleado que registró el producto (null = catálogo precargado por el sistema)

            // Control de sistema
            $table->timestamps();
            $table->softDeletes();

            // El código de barras no se repite dentro de una compañía, pero dos boticas sí pueden tener el mismo
            $table->unique(['company_id', 'barcode']);

            // Listados de la compañía ordenados por nombre y paginados (catálogos de miles de productos)
            $table->index(['company_id', 'status', 'name'], 'products_company_status_name_index');
        });

        $this->createSearchIndexes();
    }

    /**
     * Índices de trigramas (solo PostgreSQL) para las búsquedas "contiene" (ilike '%texto%')
     * en nombre, código y código de barras. Si el servidor no permite crear la extensión
     * pg_trgm, las búsquedas funcionan igual, solo sin estos índices.
     */
    private function createSearchIndexes(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $available = DB::selectOne("SELECT 1 AS ok FROM pg_available_extensions WHERE name = 'pg_trgm'");
        if (!$available) {
            return;
        }

        // Dentro de la transacción de la migración esto es un punto de guardado: si falta el
        // permiso, se deshace solo la extensión y la migración sigue
        try {
            DB::transaction(fn () => DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm'));
        } catch (\Throwable $e) {
            return;
        }

        DB::statement('CREATE INDEX products_name_trgm_index ON products USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX products_code_trgm_index ON products USING gin (code gin_trgm_ops)');
        DB::statement('CREATE INDEX products_barcode_trgm_index ON products USING gin (barcode gin_trgm_ops)');
    }

    /**
     * Reverse the migrations (los índices se van con la tabla; la extensión pg_trgm se deja).
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
