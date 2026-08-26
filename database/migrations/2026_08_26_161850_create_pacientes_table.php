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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 30)->unique();

            $table->string('tipo_documento', 20);
            $table->string('numero_documento', 30)->unique();

            $table->string('nombres', 120);
            $table->string('apellidos', 120);

            $table->date('fecha_nacimiento')->nullable();

            $table->string('telefono', 30)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('direccion', 255)->nullable();

            $table->string('nombre_contacto_emergencia', 150)->nullable();
            $table->string('telefono_contacto_emergencia', 30)->nullable();

            $table->text('observaciones')->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
