<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | referencia -> numero_operacion
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn('pagos', 'referencia')
            &&
            Schema::hasColumn('pagos', 'numero_operacion')
        ) {
            DB::table('pagos')
                ->whereNull('numero_operacion')
                ->whereNotNull('referencia')
                ->update([
                    'numero_operacion' =>
                        DB::raw('referencia'),
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | usuario_recibio_id -> usuario_registro_id
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn('pagos', 'usuario_recibio_id')
            &&
            Schema::hasColumn('pagos', 'usuario_registro_id')
        ) {
            DB::table('pagos')
                ->whereNull('usuario_registro_id')
                ->whereNotNull('usuario_recibio_id')
                ->update([
                    'usuario_registro_id' =>
                        DB::raw('usuario_recibio_id'),
                ]);
        }

        /*
         * Temporalmente dejamos las columnas antiguas.
         * Se eliminarán después de comprobar el sistema completo.
         */
    }

    public function down(): void
    {
        /*
         * No revertimos la copia porque los datos ya fueron
         * consolidados en las columnas definitivas.
         */
    }
};