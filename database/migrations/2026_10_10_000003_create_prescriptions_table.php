<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el registro de recetas médicas: los datos del paciente y del médico que acompañan
     * a una venta con productos que exigen receta o que son controlados (psicotrópicos/estupefacientes).
     */
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('order_id')->index();           // venta en la que se despachó
            $table->unsignedBigInteger('employee_id')->index();        // quién despachó

            $table->string('patient_name', 150);
            $table->string('patient_document', 15)->nullable();       // DNI / CE del paciente
            $table->string('doctor_name', 150);
            $table->string('doctor_license', 20);                      // N° de colegiatura (CMP)
            $table->string('establishment', 150)->nullable();         // establecimiento de salud que emitió la receta
            $table->string('prescription_number', 30)->nullable();
            $table->date('prescription_date');

            $table->timestamps();
        });
    }

    /**
     * Elimina el registro de recetas.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
