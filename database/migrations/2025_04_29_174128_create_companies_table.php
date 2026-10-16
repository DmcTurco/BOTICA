<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Support\InitialUser;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ruc', 11)->nullable()->unique();       // RUC del emisor (11 dígitos SUNAT)
            $table->string('address', 200)->nullable();            // dirección fiscal
            $table->string('phone', 30)->nullable();               // teléfono de contacto
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Mensualidad del servicio
            $table->decimal('monthly_fee', 10, 2)->default(0);         // monto mensual
            $table->string('billing_cycle', 10)->default('date');      // date: cada mes el mismo día · month_end: vence a fin de mes
            $table->date('trial_until')->nullable();                   // último día del periodo de prueba gratis
            $table->date('paid_until')->nullable();                    // último día con servicio cubierto (null = plan gratis, sin cobro)

            $table->rememberToken();
            $table->timestamps();
        });

        // Compañía seed (id = 1). Correo y contraseña: INITIAL_COMPANY_* del .env
        DB::table('companies')->insert(InitialUser::attributes('company') + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
