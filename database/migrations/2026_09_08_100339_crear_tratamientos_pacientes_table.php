<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'tratamientos_pacientes',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('paciente_id')
                    ->constrained('pacientes')
                    ->restrictOnDelete();

                $table->foreignId('servicio_id')
                    ->constrained('servicios')
                    ->restrictOnDelete();

                $table->foreignId('profesional_id')
                    ->nullable()
                    ->constrained('profesionales')
                    ->nullOnDelete();

                /*
                 * Precio que se acordó con el paciente.
                 *
                 * Aunque el precio del catálogo cambie después,
                 * este tratamiento conservará su precio original.
                 */
                $table->decimal(
                    'precio_acordado',
                    10,
                    2
                );

                /*
                 * Estado clínico del tratamiento.
                 *
                 * PLANIFICADO
                 * EN_PROCESO
                 * COMPLETADO
                 * CANCELADO
                 */
                $table->string(
                    'estado',
                    30
                )->default(
                    'PLANIFICADO'
                );

                $table->date('fecha_inicio')
                    ->nullable();

                $table->date('fecha_fin')
                    ->nullable();

                $table->text('observaciones')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'paciente_id',
                    'estado',
                ]);
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'tratamientos_pacientes'
        );
    }
};