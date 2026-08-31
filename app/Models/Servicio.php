<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'duracion_estimada_minutos',
        'precio_actual',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'duracion_estimada_minutos' => 'integer',
            'precio_actual' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function serviciosCita(): HasMany
    {
        return $this->hasMany(ServicioCita::class);
    }
}