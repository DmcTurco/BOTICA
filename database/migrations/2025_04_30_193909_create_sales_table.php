<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->nullable();
            $table->string('customer_document', 20)->nullable();
            $table->unsignedTinyInteger('voucher_type')->default(1)->comment('1=boleta,2=factura,3=nota');
            $table->string('voucher_number', 30)->nullable();
            $table->unsignedTinyInteger('payment_type')->default(1)->comment('1=efectivo,2=tarjeta,3=transferencia');
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
        Schema::dropIfExists('sales');
    }
};
