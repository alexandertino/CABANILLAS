<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasColumn(
                'pagos',
                'tratamiento_paciente_id'
            )
        ) {

            Schema::table(
                'pagos',
                function (Blueprint $table) {

                    $table->foreignId(
                        'tratamiento_paciente_id'
                    )
                        ->nullable()
                        ->constrained(
                            'tratamientos_pacientes'
                        )
                        ->restrictOnDelete();

                }
            );
        }
    }


    public function down(): void
    {
        if (
            Schema::hasColumn(
                'pagos',
                'tratamiento_paciente_id'
            )
        ) {

            Schema::table(
                'pagos',
                function (Blueprint $table) {

                    $table->dropConstrainedForeignId(
                        'tratamiento_paciente_id'
                    );

                }
            );
        }
    }
};