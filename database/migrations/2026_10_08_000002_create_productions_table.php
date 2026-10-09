<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas de preparaciones (lotes elaborados a partir de una fórmula)
     * y de los insumos realmente consumidos en cada una.
     */
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();       // sede donde se prepara y se descuentan los insumos
            $table->unsignedBigInteger('formula_id')->index();
            $table->unsignedBigInteger('employee_id')->index();     // empleado que registró la preparación

            $table->string('batch', 30);                            // número de lote del preparado
            $table->decimal('multiplier', 10, 4)->default(1)->comment('Veces que se preparó la fórmula base (cantidad deseada ÷ rendimiento)');
            $table->decimal('quantity_produced', 10, 2)->comment('Cantidad final obtenida (yield × multiplier)');

            $table->date('produced_at');                            // fecha de elaboración
            $table->date('expiration_date')->nullable();            // vencimiento del lote

            // Costos al momento de preparar
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);

            // Snapshot de la venta en POS al preparar (si ingresó a stock)
            $table->string('product_code', 20)->nullable()->index();

            $table->text('notes')->nullable();

            // Estado: 1=vigente, 0=anulada (se devuelven los insumos al stock)
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();

            $table->timestamps();

            // Un número de lote no se repite dentro de la compañía
            $table->unique(['company_id', 'batch']);
        });

        Schema::create('production_ingredients', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('production_id')->index();
            $table->string('product_code', 20)->index();            // insumo consumido
            $table->decimal('quantity', 12, 2);                     // cantidad descontada del stock
            $table->decimal('unit_cost', 10, 2)->default(0);        // costo unitario al momento
            $table->decimal('subtotal', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Elimina las tablas de preparaciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_ingredients');
        Schema::dropIfExists('productions');
    }
};
