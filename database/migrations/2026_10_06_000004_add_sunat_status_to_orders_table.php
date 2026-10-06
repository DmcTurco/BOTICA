<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado del envío del comprobante a SUNAT (solo boletas y facturas).
     * La nota de venta es un documento interno y queda como 'not_applicable'.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('sunat_status', 15)->default('not_applicable')->index()->after('status'); // not_applicable | pending | accepted | rejected | error
            $table->string('sunat_code', 10)->nullable()->after('sunat_status');                    // código de respuesta (0 = aceptado)
            $table->text('sunat_message')->nullable()->after('sunat_code');                         // descripción de SUNAT o del error
            $table->string('sunat_hash', 64)->nullable()->after('sunat_message');                   // resumen (DigestValue) del XML firmado
            $table->string('sunat_xml_path')->nullable()->after('sunat_hash');                      // XML firmado (disco privado)
            $table->string('sunat_cdr_path')->nullable()->after('sunat_xml_path');                  // constancia de recepción (CDR)
            $table->string('sunat_environment', 10)->nullable()->after('sunat_cdr_path');           // beta | production con que se emitió
            $table->unsignedSmallInteger('sunat_attempts')->default(0)->after('sunat_environment'); // intentos de envío
            $table->timestamp('sunat_sent_at')->nullable()->after('sunat_attempts');                // último envío
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['sunat_status']);
            $table->dropColumn([
                'sunat_status', 'sunat_code', 'sunat_message', 'sunat_hash',
                'sunat_xml_path', 'sunat_cdr_path', 'sunat_environment',
                'sunat_attempts', 'sunat_sent_at',
            ]);
        });
    }
};
