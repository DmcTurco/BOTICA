<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de movimientos de stock (kardex).
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();    // compañía a la que pertenece el movimiento
            $table->unsignedBigInteger('branch_id')->index();     // sede donde ocurrió el movimiento de stock
            $table->string('product_code', 20)->index();          // producto afectado

            // Tipo: entrada (compra) o salida (venta)
            $table->enum('type', ['entrada', 'salida', 'ajuste'])
                  ->comment('entrada=compra, salida=venta, ajuste=corrección manual');

            // Referencia al documento origen
            $table->string('reference_type', 20)->nullable()
                  ->comment('purchase, order, manual');
            $table->unsignedBigInteger('reference_id')->nullable()->index();

            $table->integer('quantity')->comment('Siempre positivo; el tipo indica si suma o resta');
            $table->decimal('unit_cost', 10, 2)->default(0)->comment('Costo unitario al momento del movimiento');
            $table->integer('balance')->comment('Stock resultante después de este movimiento');

            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de movimientos de stock.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
