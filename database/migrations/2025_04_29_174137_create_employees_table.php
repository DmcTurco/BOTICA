<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();    // compañía a la que pertenece
            $table->unsignedBigInteger('branch_id')->index();     // sede principal del empleado
            $table->unsignedBigInteger('role_id')->index();       // rol: branch_admin (2) o employee (3)
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // role_id 2 = branch_admin (ver roles seeder)
        DB::table('employees')->insert([
            'company_id' => 1,
            'branch_id'  => 1,
            'role_id'    => 2,
            'name'       => 'Employee Name',
            'email'      => 'employee@example.com',
            'password'   => Hash::make('0000'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
