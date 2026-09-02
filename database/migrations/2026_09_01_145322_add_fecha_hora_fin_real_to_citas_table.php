<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table
                ->timestamp('fecha_hora_fin_real')
                ->nullable()
                ->after('fecha_hora_fin');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn(
                'fecha_hora_fin_real'
            );
        });
    }
};
