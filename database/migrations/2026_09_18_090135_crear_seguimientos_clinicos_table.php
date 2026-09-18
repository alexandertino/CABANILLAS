<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'seguimientos_clinicos',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('paciente_id')
                    ->constrained('pacientes')
                    ->restrictOnDelete();

                $table
                    ->foreignId('tratamiento_paciente_id')
                    ->nullable()
                    ->constrained('tratamientos_pacientes')
                    ->nullOnDelete();

                $table
                    ->foreignId('cita_id')
                    ->nullable()
                    ->constrained('citas')
                    ->nullOnDelete();

                $table
                    ->foreignId('profesional_id')
                    ->constrained('profesionales')
                    ->restrictOnDelete();

                $table
                    ->foreignId('usuario_creador_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('fecha_seguimiento');

                $table->string('titulo', 150);

                $table->text('observaciones')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'paciente_id',
                    'fecha_seguimiento',
                ]);

                $table->index([
                    'tratamiento_paciente_id',
                    'fecha_seguimiento',
                ]);

                $table->index([
                    'profesional_id',
                    'fecha_seguimiento',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'seguimientos_clinicos'
        );
    }
};