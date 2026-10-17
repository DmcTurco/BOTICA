<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Venta por presentaciones (blíster, caja): cada ítem guarda la presentación vendida y cuántas
 * unidades base trae, porque el stock se descuenta en unidades base (quantity × units_per_item).
 * Las ventas anteriores quedan como unidad base (units_per_item = 1).
 * Solo agrega lo que falte, por si alguna base ya tiene las columnas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('order_detail', 'presentation_id')) {
                $table->unsignedBigInteger('presentation_id')->nullable()->index()->after('product_name'); // null = unidad base
            }
            if (!Schema::hasColumn('order_detail', 'units_per_item')) {
                $table->decimal('units_per_item', 10, 2)->default(1)->after('presentation_id');          // blíster x10 = 10
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_detail', function (Blueprint $table) {
            if (Schema::hasColumn('order_detail', 'presentation_id')) {
                $table->dropIndex(['presentation_id']);
                $table->dropColumn('presentation_id');
            }
            if (Schema::hasColumn('order_detail', 'units_per_item')) {
                $table->dropColumn('units_per_item');
            }
        });
    }
};
