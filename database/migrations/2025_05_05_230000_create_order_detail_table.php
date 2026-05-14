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
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_detail');
    }
};
