<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index()->comment('Empresa a la que pertenece el cliente');
            $table->string('code', 20)->unique()->comment('Código autogenerado: C-000001');
            $table->string('name', 150)->comment('Nombre completo o razón social');
            $table->unsignedTinyInteger('document_type_id')->nullable()->index()->comment('FK a document_types');
            $table->string('document_number', 20)->nullable()->index();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('address', 200)->nullable();
            $table->unsignedTinyInteger('status')->default(1)->comment('1=activo, 0=inactivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
