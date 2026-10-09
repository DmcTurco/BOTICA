<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las tablas de traspasos de mercadería entre sedes y su detalle.
     */
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('from_branch_id')->index();    // sede que despacha
            $table->unsignedBigInteger('to_branch_id')->index();      // sede que recibe
            $table->unsignedBigInteger('employee_id')->index();       // quién registró el traspaso

            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('status')->default(1)->comment('1=vigente, 0=anulado');
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();

            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('stock_transfer_id')->index();
            $table->string('product_code', 20)->index();
            $table->decimal('quantity', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Elimina las tablas de traspasos.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
