<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de sedes/sucursales.
     * Cada compañía puede tener múltiples sedes.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();    // compañía a la que pertenece la sede
            $table->string('name', 100);                          // nombre de la sede
            $table->string('address', 200)->nullable();           // dirección física
            $table->string('phone', 30)->nullable();              // teléfono de contacto
            $table->string('email', 100)->nullable();             // email de la sede
            $table->string('sunat_establishment_code', 4)->default('0000'); // código de establecimiento anexo SUNAT (0000 = domicilio fiscal)
            $table->unsignedTinyInteger('uses_fiscal_establishment')->default(0)
                  ->comment('1 = el local aún no es anexo en SUNAT: emite con el código del domicilio fiscal (0000); 0 = usa su propio código');
            $table->unsignedTinyInteger('status')->default(1)
                  ->comment('1=activa, 0=inactiva');
            $table->timestamps();
        });

        // Sede principal de la compañía seed
        DB::table('branches')->insert([
            'company_id' => 1,
            'name'       => 'Sede Principal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Elimina la tabla de sedes.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
