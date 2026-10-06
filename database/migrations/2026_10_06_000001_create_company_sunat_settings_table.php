<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuración de facturación electrónica (SUNAT) por compañía.
     * Una fila por compañía: datos fiscales, credenciales SOL y certificado digital.
     */
    public function up(): void
    {
        Schema::create('company_sunat_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();               // compañía dueña de la configuración

            // Datos fiscales (el RUC vive en companies.ruc)
            $table->string('legal_name', 150)->nullable();                    // razón social
            $table->string('trade_name', 150)->nullable();                    // nombre comercial
            $table->string('fiscal_address', 200)->nullable();                // dirección fiscal
            $table->string('ubigeo', 6)->nullable();                          // código INEI de 6 dígitos
            $table->string('department', 60)->nullable();
            $table->string('province', 60)->nullable();
            $table->string('district', 60)->nullable();

            // Ambiente y credenciales SOL (secundarias)
            $table->string('environment', 10)->default('beta');               // beta | production
            $table->string('sol_user', 50)->nullable();
            $table->text('sol_password')->nullable();                         // cifrada

            // Certificado digital (.pfx / .p12) guardado cifrado en disco privado
            $table->string('certificate_path')->nullable();
            $table->text('certificate_password')->nullable();                 // cifrada
            $table->string('certificate_subject')->nullable();                // titular (para mostrar)
            $table->date('certificate_expires_at')->nullable();

            $table->string('boleta_mode', 10)->default('individual');         // individual | summary (resumen diario)

            $table->boolean('enabled')->default(false);                       // emisión electrónica activa
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_sunat_settings');
    }
};
