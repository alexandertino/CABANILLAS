<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (
                Schema::hasColumn(
                    'pagos',
                    'usuario_recibio_id'
                )
            ) {
                $table->dropForeign(
                    'pagos_usuario_recibio_id_foreign'
                );

                $table->dropColumn(
                    'usuario_recibio_id'
                );
            }

            if (
                Schema::hasColumn(
                    'pagos',
                    'referencia'
                )
            ) {
                $table->dropColumn(
                    'referencia'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table
                ->string(
                    'referencia',
                    150
                )
                ->nullable();

            $table
                ->foreignId(
                    'usuario_recibio_id'
                )
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }
};