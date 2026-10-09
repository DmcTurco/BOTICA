<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas de fórmulas magistrales (recetas del químico farmacéutico)
     * y de sus insumos.
     */
    public function up(): void
    {
        Schema::create('formulas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();      // compañía dueña de la fórmula
            $table->string('code', 20);                             // código interno (ej. FM-0001)
            $table->string('name', 150);                            // nombre del preparado
            $table->string('pharmaceutical_form', 50)->nullable()
                  ->comment('Forma farmacéutica: crema, jarabe, cápsulas, solución...');
            $table->text('description')->nullable();

            // Rendimiento: lo que resulta de preparar la fórmula una vez
            $table->decimal('yield_quantity', 10, 2)->comment('Cantidad de producto final que rinde la fórmula base');
            $table->unsignedBigInteger('yield_unit_id')->nullable()->index(); // unidad del producto final (frasco, g, ml...)

            $table->text('procedure')->nullable()->comment('Modo de preparación');
            $table->string('storage', 255)->nullable()->comment('Condiciones de conservación');
            $table->unsignedInteger('shelf_life_days')->nullable()->comment('Vida útil en días desde la elaboración');

            // Venta en el POS: si está activo, el preparado ingresa al stock del producto vinculado
            $table->boolean('sell_in_pos')->default(false);
            $table->string('product_code', 20)->nullable()->index()
                  ->comment('Producto del catálogo que recibe el stock cuando sell_in_pos = true');

            $table->smallInteger('status')->default(1)->comment('1=activa, 0=inactiva');
            $table->unsignedBigInteger('employee_id')->index();     // empleado que registró la fórmula

            $table->timestamps();
            $table->softDeletes();

            // Código único dentro de la compañía
            $table->unique(['company_id', 'code']);
        });

        Schema::create('formula_ingredients', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('formula_id')->index();
            $table->string('product_code', 20)->index();            // insumo (producto del catálogo)
            $table->decimal('quantity', 12, 2)->comment('Cantidad del insumo por fórmula base, en la unidad del producto');
            $table->string('notes', 255)->nullable();               // ej. "tamizar", "disolver en caliente"

            $table->timestamps();
        });
    }

    /**
     * Elimina las tablas de fórmulas.
     */
    public function down(): void
    {
        Schema::dropIfExists('formula_ingredients');
        Schema::dropIfExists('formulas');
    }
};
