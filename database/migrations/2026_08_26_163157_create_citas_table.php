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
        Schema::create('citas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('profesional_id')
                ->constrained('profesionales')
                ->restrictOnDelete();

            $table->foreignId('consultorio_id')
                ->nullable()
                ->constrained('consultorios')
                ->nullOnDelete();

            $table->foreignId('estado_cita_id')
                ->constrained('estados_cita')
                ->restrictOnDelete();

            $table->dateTime('fecha_hora_inicio');
            $table->dateTime('fecha_hora_fin');

            $table->string('motivo', 255)->nullable();
            $table->text('observaciones')->nullable();

            $table->dateTime('fecha_cancelacion')->nullable();
            $table->text('motivo_cancelacion')->nullable();

            $table->foreignId('usuario_creador_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('fecha_hora_inicio');
            $table->index(['profesional_id', 'fecha_hora_inicio']);
            $table->index(['paciente_id', 'fecha_hora_inicio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
