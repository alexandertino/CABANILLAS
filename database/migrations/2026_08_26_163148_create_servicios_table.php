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
            Schema::create('servicios', function (Blueprint $table) {
                $table->id();

                $table->string('codigo', 30)->unique();
                $table->string('nombre', 150);

                $table->text('descripcion')->nullable();

                $table->unsignedInteger('duracion_estimada_minutos');

                $table->decimal('precio_actual', 10, 2);

                $table->boolean('activo')->default(true);

                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
