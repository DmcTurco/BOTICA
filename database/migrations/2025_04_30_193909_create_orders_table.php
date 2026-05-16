<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->string('type_code', 20)->comment('Código interno: BOLETA, FACTURA, PRODUCTO, COMPRA...');
            $table->string('name', 50)->comment('Nombre legible del tipo de documento');
            $table->string('series', 10)->comment('Prefijo de serie: B001, F001, P, CMP...');
            $table->unsignedBigInteger('current_number')->default(0)->comment('Último correlativo usado');
            $table->unsignedTinyInteger('digits')->default(6)->comment('Dígitos del correlativo: 8 para SUNAT, 6 para internos');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['type_code', 'series']);
        });

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
            $table->string('voucher_number', 30)->nullable()->unique(); // índice único: nunca dos órdenes con el mismo número
            $table->unsignedTinyInteger('payment_type')->default(1)->comment('1=efectivo,2=tarjeta,3=transferencia,4=yape');
            $table->string('operation_number', 50)->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('igv', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('document_series');
        Schema::dropIfExists('document_types');
    }
};
