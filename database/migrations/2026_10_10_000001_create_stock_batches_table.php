<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas de lotes: el desglose del stock de cada producto por lote y vencimiento,
     * y las asignaciones que dejan cada venta o consumo para poder revertirlos al mismo lote.
     * branch_stock sigue siendo el total oficial; los lotes son su desglose (FEFO).
     */
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->string('product_code', 20)->index();

            $table->string('batch', 30)->nullable();                   // número de lote del fabricante o del preparado
            $table->date('expiration_date')->nullable();               // sin fecha = no vence
            $table->decimal('quantity_initial', 10, 2);               // unidades que entraron con el lote
            $table->decimal('quantity_remaining', 10, 2);             // unidades que quedan en la sede
            $table->decimal('unit_cost', 10, 2)->default(0);

            $table->string('source_type', 20)->comment('purchase | production | transfer');
            $table->unsignedBigInteger('source_id')->nullable();       // documento que lo originó

            $table->timestamps();

            // Búsqueda FEFO: lotes de un producto en una sede ordenados por vencimiento
            $table->index(['branch_id', 'product_code', 'expiration_date'], 'stock_batches_fefo_index');
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('batch_allocations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('stock_batch_id')->index();
            $table->string('reference_type', 20)->comment('order | production | transfer');
            $table->unsignedBigInteger('reference_id');
            $table->decimal('quantity', 10, 2);

            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Elimina las tablas de lotes.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_allocations');
        Schema::dropIfExists('stock_batches');
    }
};
