<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // Unidad, Blister, Caja, Frasco, etc.
            $table->string('abbreviation', 10);
            $table->string('sunat_code', 3)->default('NIU'); // catálogo SUNAT 03: NIU unidad, BX caja, BO frasco...
            $table->smallInteger('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
