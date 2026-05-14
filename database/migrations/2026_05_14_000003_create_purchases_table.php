<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de compras (boletas/facturas de ingreso de stock).
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();

            // Tipo de documento: 1=boleta, 2=factura, 3=nota de ingreso
            $table->unsignedTinyInteger('document_type')->default(1)
                  ->comment('1=boleta, 2=factura, 3=nota de ingreso');

            $table->string('document_number', 30)->nullable()->comment('Número de boleta/factura del proveedor');
            $table->string('supplier', 150)->nullable()->comment('Nombre del proveedor o laboratorio');

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0)->comment('IGV u otro impuesto');
            $table->decimal('total', 10, 2)->default(0);

            // Estado: 1=confirmada, 0=anulada
            $table->unsignedTinyInteger('status')->default(1)
                  ->comment('1=confirmada, 0=anulada');

            $table->text('notes')->nullable();
            $table->timestamp('purchased_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de compras.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
