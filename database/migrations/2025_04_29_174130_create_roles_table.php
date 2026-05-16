<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de roles del sistema.
     * Los roles son globales (no pertenecen a ninguna compañía).
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();         // company_admin, branch_admin, employee
            $table->string('description', 150)->nullable();
            $table->timestamps();
        });

        // Roles fijos del sistema
        DB::table('roles')->insert([
            ['name' => 'company_admin', 'description' => 'Administrador de la compañía', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'branch_admin',  'description' => 'Administrador de sede',         'created_at' => now(), 'updated_at' => now()],
            ['name' => 'employee',      'description' => 'Empleado de sede',               'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Elimina la tabla de roles.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
