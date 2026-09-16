<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MetodoPagoSeeder extends Seeder
{
    public function run(): void
    {
        $metodos = [

            [
                'codigo' => 'EFECTIVO',
                'nombre' => 'Efectivo',
                'activo' => true,
            ],

            [
                'codigo' => 'YAPE',
                'nombre' => 'Yape',
                'activo' => true,
            ],

            [
                'codigo' => 'PLIN',
                'nombre' => 'Plin',
                'activo' => true,
            ],

            [
                'codigo' => 'TARJETA',
                'nombre' => 'Tarjeta',
                'activo' => true,
            ],

            [
                'codigo' => 'TRANSFERENCIA',
                'nombre' => 'Transferencia bancaria',
                'activo' => true,
            ],

        ];


        foreach ($metodos as $metodo) {

            DB::table('metodos_pago')
                ->updateOrInsert(

                    [
                        'codigo' =>
                            $metodo['codigo'],
                    ],

                    [
                        'nombre' =>
                            $metodo['nombre'],

                        'activo' =>
                            $metodo['activo'],
                    ]
                );
        }
    }
}