<?php

namespace Database\Seeders;

use App\Models\EstadoCita;
use Illuminate\Database\Seeder;

class EstadoCitaSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            [
                'codigo' => 'PENDIENTE',
                'nombre' => 'Pendiente',
                'es_final' => false,
                'activo' => true,
            ],
            [
                'codigo' => 'CONFIRMADA',
                'nombre' => 'Confirmada',
                'es_final' => false,
                'activo' => true,
            ],
            [
                'codigo' => 'EN_ATENCION',
                'nombre' => 'En atención',
                'es_final' => false,
                'activo' => true,
            ],
            [
                'codigo' => 'ATENDIDA',
                'nombre' => 'Atendida',
                'es_final' => true,
                'activo' => true,
            ],
            [
                'codigo' => 'CANCELADA',
                'nombre' => 'Cancelada',
                'es_final' => true,
                'activo' => true,
            ],
            [
                'codigo' => 'NO_ASISTIO',
                'nombre' => 'No asistió',
                'es_final' => true,
                'activo' => true,
            ],
            [
                'codigo' => 'REPROGRAMADA',
                'nombre' => 'Reprogramada',
                'es_final' => false,
                'activo' => true,
            ],
        ];

        foreach ($estados as $estado) {
            EstadoCita::updateOrCreate(
                [
                    'codigo' => $estado['codigo'],
                ],
                $estado
            );
        }
    }
}