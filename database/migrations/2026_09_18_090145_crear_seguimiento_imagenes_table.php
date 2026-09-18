<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'seguimiento_imagenes',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('seguimiento_clinico_id')
                    ->constrained('seguimientos_clinicos')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('usuario_subio_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('ruta', 500);

                $table->string(
                    'nombre_original',
                    255
                );

                $table->string(
                    'nombre_archivo',
                    255
                );

                $table->string(
                    'mime_type',
                    100
                );

                $table
                    ->unsignedBigInteger(
                        'tamanio_bytes'
                    );

                $table
                    ->unsignedInteger('ancho')
                    ->nullable();

                $table
                    ->unsignedInteger('alto')
                    ->nullable();

                $table
                    ->unsignedSmallInteger('orden')
                    ->default(0);

                $table
                    ->string('descripcion', 255)
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'seguimiento_clinico_id',
                    'orden',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'seguimiento_imagenes'
        );
    }
};