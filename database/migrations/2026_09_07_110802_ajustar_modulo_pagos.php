<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | MÉTODOS DE PAGO
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('metodos_pago')) {

            Schema::create('metodos_pago', function (Blueprint $table) {

                $table->id();

                $table->string('codigo', 30)->unique();

                $table->string('nombre', 100);

                $table->boolean('activo')->default(true);

                $table->timestamps();
            });

        } else {

            Schema::table('metodos_pago', function (Blueprint $table) {

                if (!Schema::hasColumn('metodos_pago', 'codigo')) {
                    $table->string('codigo', 30)->nullable();
                }

                if (!Schema::hasColumn('metodos_pago', 'nombre')) {
                    $table->string('nombre', 100)->nullable();
                }

                if (!Schema::hasColumn('metodos_pago', 'activo')) {
                    $table->boolean('activo')->default(true);
                }
            });
        }


        /*
        |--------------------------------------------------------------------------
        | PAGOS
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('pagos')) {

            Schema::create('pagos', function (Blueprint $table) {

                $table->id();

                $table->foreignId('cita_id')
                    ->constrained('citas')
                    ->restrictOnDelete();

                $table->foreignId('metodo_pago_id')
                    ->constrained('metodos_pago')
                    ->restrictOnDelete();

                $table->decimal('monto', 10, 2);

                $table->dateTime('fecha_pago');

                $table->string('numero_operacion', 100)
                    ->nullable();

                $table->text('observaciones')
                    ->nullable();

                $table->string('estado', 20)
                    ->default('REGISTRADO');

                $table->foreignId('usuario_registro_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->dateTime('fecha_anulacion')
                    ->nullable();

                $table->text('motivo_anulacion')
                    ->nullable();

                $table->foreignId('usuario_anulacion_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
            });

        } else {

            /*
             * Solo agregamos columnas que todavía
             * no existan.
             */

            if (!Schema::hasColumn('pagos', 'cita_id')) {

                Schema::table('pagos', function (Blueprint $table) {

                    $table->foreignId('cita_id')
                        ->nullable()
                        ->constrained('citas')
                        ->restrictOnDelete();
                });
            }


            if (!Schema::hasColumn('pagos', 'metodo_pago_id')) {

                Schema::table('pagos', function (Blueprint $table) {

                    $table->foreignId('metodo_pago_id')
                        ->nullable()
                        ->constrained('metodos_pago')
                        ->restrictOnDelete();
                });
            }


            if (!Schema::hasColumn('pagos', 'monto')) {

                Schema::table('pagos', function (Blueprint $table) {

                    $table->decimal('monto', 10, 2)
                        ->default(0);
                });
            }


            if (!Schema::hasColumn('pagos', 'fecha_pago')) {

                Schema::table('pagos', function (Blueprint $table) {

                    $table->dateTime('fecha_pago')
                        ->nullable();
                });
            }


            if (!Schema::hasColumn('pagos', 'numero_operacion')) {

                Schema::table('pagos', function (Blueprint $table) {

                    $table->string(
                        'numero_operacion',
                        100
                    )->nullable();
                });
            }


            if (!Schema::hasColumn('pagos', 'observaciones')) {

                Schema::table('pagos', function (Blueprint $table) {

                    $table->text('observaciones')
                        ->nullable();
                });
            }


            if (!Schema::hasColumn('pagos', 'estado')) {

                Schema::table('pagos', function (Blueprint $table) {

                    $table->string(
                        'estado',
                        20
                    )->default('REGISTRADO');
                });
            }


            if (!Schema::hasColumn('pagos', 'usuario_registro_id')) {

                Schema::table('pagos', function (Blueprint $table) {

                    $table->foreignId('usuario_registro_id')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();
                });
            }


            if (!Schema::hasColumn('pagos', 'fecha_anulacion')) {

                Schema::table('pagos', function (Blueprint $table) {

                    $table->dateTime('fecha_anulacion')
                        ->nullable();
                });
            }


            if (!Schema::hasColumn('pagos', 'motivo_anulacion')) {

                Schema::table('pagos', function (Blueprint $table) {

                    $table->text('motivo_anulacion')
                        ->nullable();
                });
            }


            if (!Schema::hasColumn('pagos', 'usuario_anulacion_id')) {

                Schema::table('pagos', function (Blueprint $table) {

                    $table->foreignId('usuario_anulacion_id')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();
                });
            }
        }
    }


    public function down(): void
    {
        /*
         * No eliminaremos tablas completas porque podrían
         * existir desde migraciones anteriores del proyecto.
         *
         * En esta etapa preferimos preservar información.
         */
    }
};