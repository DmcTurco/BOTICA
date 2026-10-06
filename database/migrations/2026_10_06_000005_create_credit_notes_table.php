<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notas de crédito electrónicas (anulación total de una boleta o factura ya aceptada por SUNAT).
     * Cada nota referencia la venta original; sus importes y detalle se toman de ella.
     * Las series de nota de crédito de cada sede las crea DocumentSeries::crearSeriesParaSede().
     */
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('order_id')->unique();       // venta que se anula (una sola nota total por venta)
            $table->unsignedBigInteger('employee_id')->index();     // quien emitió la nota
            $table->string('voucher_number', 30);                   // BC01-00000001 / FC01-00000001
            $table->string('reason_code', 2);                       // catálogo SUNAT 09: 01, 02, 06
            $table->string('reason_text', 250);

            // Importes (copia de la venta anulada)
            $table->decimal('taxable_amount', 10, 2)->default(0);
            $table->decimal('exonerated_amount', 10, 2)->default(0);
            $table->decimal('unaffected_amount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('igv', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            // Estado del envío a SUNAT (igual que en orders)
            $table->string('sunat_status', 15)->default('pending')->index();
            $table->string('sunat_code', 10)->nullable();
            $table->text('sunat_message')->nullable();
            $table->string('sunat_hash', 64)->nullable();
            $table->string('sunat_xml_path')->nullable();
            $table->string('sunat_cdr_path')->nullable();
            $table->string('sunat_environment', 10)->nullable();
            $table->unsignedSmallInteger('sunat_attempts')->default(0);
            $table->timestamp('sunat_sent_at')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'voucher_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
