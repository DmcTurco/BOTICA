<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Fuera de transacción: si PostgreSQL no permite crear pg_trgm, se sigue sin los índices de búsqueda
    public $withinTransaction = false;

    /**
     * Índices para listar y buscar en catálogos grandes (miles de productos precargados):
     *  - (company_id, status, name): listados de la compañía ordenados por nombre, paginados.
     *  - Trigramas (solo PostgreSQL): búsquedas "contiene" con ilike '%texto%' en nombre, código y código de barras.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['company_id', 'status', 'name'], 'products_company_status_name_index');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        } catch (\Throwable $e) {
            // Sin permiso para la extensión: las búsquedas funcionan igual, solo sin este índice
            return;
        }

        DB::statement('CREATE INDEX IF NOT EXISTS products_name_trgm_index ON products USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_code_trgm_index ON products USING gin (code gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS products_barcode_trgm_index ON products USING gin (barcode gin_trgm_ops)');
    }

    /**
     * Quita los índices (la extensión pg_trgm se deja, otras tablas podrían usarla).
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS products_name_trgm_index');
            DB::statement('DROP INDEX IF EXISTS products_code_trgm_index');
            DB::statement('DROP INDEX IF EXISTS products_barcode_trgm_index');
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_company_status_name_index');
        });
    }
};
