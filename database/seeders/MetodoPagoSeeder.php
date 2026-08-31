<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use Illuminate\Database\Seeder;

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
                'nombre' => 'Transferencia',
                'activo' => true,
            ],
        ];

        foreach ($metodos as $metodo) {
            MetodoPago::updateOrCreate(
                [
                    'codigo' => $metodo['codigo'],
                ],
                $metodo
            );
        }
    }
}