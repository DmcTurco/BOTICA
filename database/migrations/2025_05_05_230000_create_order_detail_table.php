<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();        // orden a la que pertenece
            $table->string('product_code', 20)->index();            // producto vendido
            $table->string('product_name', 150);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('quantity', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);  // descuento de la línea (sin IGV), ya restado del subtotal
            $table->decimal('subtotal', 10, 2);                     // base imponible de la línea (sin IGV, con descuento)
            $table->string('igv_affectation', 2)->default('20');    // 10 gravado, 20 exonerado, 30 inafecto
            $table->decimal('igv_amount', 10, 2)->default(0);       // IGV de la línea
            $table->string('unit_code', 3)->default('NIU');         // unidad de medida SUNAT
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_detail');
    }
};
