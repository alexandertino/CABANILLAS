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
        Schema::create('historial_estados_cita', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cita_id')
                ->constrained('citas')
                ->cascadeOnDelete();

            $table->foreignId('estado_anterior_id')
                ->nullable()
                ->constrained('estados_cita')
                ->nullOnDelete();

            $table->foreignId('estado_nuevo_id')
                ->constrained('estados_cita')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('motivo')->nullable();

            $table->dateTime('fecha_cambio');

            $table->index(['cita_id', 'fecha_cambio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estados_cita');
    }
};
