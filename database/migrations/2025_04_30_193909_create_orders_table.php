<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de tipos de documento de identidad (códigos SUNAT)
        Schema::create('document_types', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 5)->unique()->comment('Código SUNAT');
            $table->string('name', 50);
            $table->string('description', 100)->nullable();
            $table->unsignedTinyInteger('digits')->nullable()->comment('Longitud esperada del número, null = variable');
            $table->boolean('active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
        });

        // Series y correlativos de documentos del sistema (genérico)
        Schema::create('document_series', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index(); // compañía (null = correlativo global interno)
            $table->unsignedBigInteger('branch_id')->nullable()->index();  // sede (solo los comprobantes de venta llevan serie por sede)
            $table->string('type_code', 20)->comment('Código interno: BOLETA, FACTURA, NOTA_VENTA, NOTA_CREDITO_*, PRODUCTO, COMPRA...');
            $table->string('name', 50)->comment('Nombre legible del tipo de documento');
            $table->string('series', 10)->comment('Prefijo de serie: B001, F001, P, CMP...');
            $table->unsignedBigInteger('current_number')->default(0)->comment('Último correlativo usado');
            $table->unsignedTinyInteger('digits')->default(6)->comment('Dígitos del correlativo: 8 para SUNAT, 6 para internos');
            $table->boolean('active')->default(true);
            $table->timestamps();

            // SUNAT: la serie es única por compañía (RUC) y tipo de comprobante
            $table->unique(['company_id', 'type_code', 'series']);
        });

        // NULL no cuenta como igual en un índice único: los correlativos globales
        // (company_id NULL) conservan su unicidad con un índice parcial.
        DB::statement('CREATE UNIQUE INDEX document_series_global_unique ON document_series (type_code, series) WHERE company_id IS NULL');

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();                   // compañía a la que pertenece la orden
            $table->unsignedBigInteger('branch_id')->index();                    // sede donde se realizó la venta
            $table->unsignedBigInteger('cash_register_id')->nullable()->index(); // caja en la que se registró
            $table->unsignedBigInteger('employee_id')->index();                  // vendedor/cajero que registró la orden
            $table->unsignedBigInteger('client_id')->nullable()->index();        // FK lógica a clients.id (null = venta anónima)
            $table->string('customer_name')->nullable();
            $table->unsignedTinyInteger('document_type_id')->nullable()->index(); // tipo de doc del cliente (FK a document_types)
            $table->string('customer_document', 20)->nullable();
            $table->unsignedTinyInteger('voucher_type')->default(1)->comment('1=boleta,2=factura,3=nota');
            $table->string('voucher_number', 30)->nullable();           // se numera por compañía (cada RUC numera aparte)
            $table->unsignedTinyInteger('payment_type')->default(1)->comment('1=efectivo,2=tarjeta,3=transferencia,4=yape');
            $table->string('operation_number', 50)->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);             // suma de las bases (gravado + exonerado + inafecto)
            $table->decimal('taxable_amount', 10, 2)->default(0);       // operaciones gravadas
            $table->decimal('exonerated_amount', 10, 2)->default(0);    // operaciones exoneradas
            $table->decimal('unaffected_amount', 10, 2)->default(0);    // operaciones inafectas
            $table->decimal('igv', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(1);

            // Estado del envío a SUNAT (solo boletas y facturas; la nota de venta es 'not_applicable')
            $table->string('sunat_status', 15)->default('not_applicable')->index(); // not_applicable | pending | accepted | rejected | error
            $table->string('sunat_code', 10)->nullable();               // código de respuesta (0 = aceptado)
            $table->text('sunat_message')->nullable();                  // descripción de SUNAT o del error
            $table->string('sunat_hash', 64)->nullable();               // resumen (DigestValue) del XML firmado
            $table->string('sunat_xml_path')->nullable();               // XML firmado (disco privado)
            $table->string('sunat_cdr_path')->nullable();               // constancia de recepción (CDR)
            $table->string('sunat_environment', 10)->nullable();        // beta | production con que se emitió
            $table->unsignedSmallInteger('sunat_attempts')->default(0); // intentos de envío
            $table->timestamp('sunat_sent_at')->nullable();             // último envío

            $table->timestamps();

            $table->unique(['company_id', 'voucher_number']);          // nunca dos órdenes de la misma compañía con el mismo número
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('document_series');
        Schema::dropIfExists('document_types');
    }
};
