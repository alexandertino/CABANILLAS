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
        Schema::create('profesionales', function (Blueprint $table) {
            $table->id();

            $table->string('nombres', 120);
            $table->string('apellidos', 120);

            $table->string('numero_documento', 30)->unique();
            $table->string('numero_colegiatura', 50)->unique();

            $table->string('telefono', 30)->nullable();
            $table->string('correo', 150)->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profesionales');
    }
};
