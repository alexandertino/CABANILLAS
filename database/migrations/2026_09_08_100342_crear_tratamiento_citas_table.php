<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'tratamiento_citas',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId(
                    'tratamiento_paciente_id'
                )
                    ->constrained(
                        'tratamientos_pacientes'
                    )
                    ->restrictOnDelete();

                /*
                 * Una cita solamente puede corresponder
                 * a un tratamiento en nuestro diseño actual.
                 */
                $table->foreignId('cita_id')
                    ->unique()
                    ->constrained('citas')
                    ->restrictOnDelete();

                $table->unsignedInteger(
                    'numero_sesion'
                )->nullable();

                $table->text(
                    'observaciones'
                )->nullable();

                $table->timestamps();

                $table->index(
                    'tratamiento_paciente_id'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'tratamiento_citas'
        );
    }
};