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
                ->foreignId('cita_origen_id')
                ->nullable()
                ->constrained('citas')
                ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {

            $table->dropConstrainedForeignId(
                'cita_origen_id'
            );

        });
    }
};