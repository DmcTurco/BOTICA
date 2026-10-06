<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resúmenes diarios de boletas enviados a SUNAT.
     * Cada resumen agrupa las boletas emitidas en una fecha (identificador RC-AAAAMMDD-n).
     */
    public function up(): void
    {
        Schema::create('sunat_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->date('reference_date');                                   // fecha de emisión de las boletas
            $table->date('generation_date');                                  // fecha en que se generó el resumen
            $table->unsignedSmallInteger('correlative');                      // correlativo del día de generación
            $table->string('identifier', 20);                                 // RC-AAAAMMDD-n
            $table->unsignedSmallInteger('documents_count')->default(0);      // boletas incluidas
            $table->string('ticket', 40)->nullable();                         // ticket que entrega SUNAT al recibir
            $table->string('status', 15)->default('pending')->index();        // pending | processing | accepted | rejected | error
            $table->string('code', 10)->nullable();
            $table->text('message')->nullable();
            $table->string('environment', 10)->nullable();                    // beta | production
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunat_summaries');
    }
};
