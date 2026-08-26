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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paciente_id')
                ->constrained('pacientes')
                ->restrictOnDelete();

            $table->foreignId('cita_id')
                ->nullable()
                ->constrained('citas')
                ->nullOnDelete();

            $table->foreignId('metodo_pago_id')
                ->constrained('metodos_pago')
                ->restrictOnDelete();

            $table->decimal('monto', 10, 2);

            $table->dateTime('fecha_pago');

            $table->string('referencia', 150)->nullable();

            $table->text('observaciones')->nullable();

            $table->foreignId('usuario_recibio_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('estado', 30)->default('registrado');

            $table->timestamps();

            $table->index('fecha_pago');
            $table->index(['paciente_id', 'fecha_pago']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
