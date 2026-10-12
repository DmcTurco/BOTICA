<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea proveedores, pagos a proveedores (cuentas por pagar) y órdenes de compra.
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->string('ruc', 11)->nullable();                     // RUC del proveedor (opcional)
            $table->string('name', 150);
            $table->string('contact', 100)->nullable();                // persona de contacto
            $table->string('phone', 30)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('address', 200)->nullable();
            $table->text('notes')->nullable();
            $table->smallInteger('status')->default(1)->comment('1=activo, 0=inactivo');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'ruc']);                     // un RUC no se repite en la compañía
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('purchase_id')->index();        // compra a crédito que se está pagando
            $table->decimal('amount', 10, 2);
            $table->unsignedTinyInteger('method')->default(1)->comment('1=efectivo,2=tarjeta,3=transferencia,4=yape');
            $table->string('reference', 50)->nullable();               // N° de operación o voucher
            $table->date('paid_at');
            $table->unsignedBigInteger('employee_id')->index();        // quién registró el pago
            $table->string('notes', 255)->nullable();

            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();          // sede que recibirá la mercadería
            $table->unsignedBigInteger('supplier_id')->index();
            $table->unsignedBigInteger('employee_id')->index();

            $table->string('number', 20);                              // OC-0001
            $table->string('status', 12)->default('pending')->comment('pending | received | cancelled');
            $table->date('expected_date')->nullable();                 // cuándo se espera la entrega
            $table->text('notes')->nullable();
            $table->decimal('total', 10, 2)->default(0);

            $table->timestamps();

            $table->unique(['company_id', 'number']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_order_id')->index();
            $table->string('product_code', 20)->index();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Elimina las tablas de proveedores y órdenes de compra.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('suppliers');
    }
};
