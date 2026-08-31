<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoCita extends Model
{
    protected $table = 'estados_cita';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'es_final',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'es_final' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function historialAnterior(): HasMany
    {
        return $this->hasMany(
            HistorialEstadoCita::class,
            'estado_anterior_id'
        );
    }

    public function historialNuevo(): HasMany
    {
        return $this->hasMany(
            HistorialEstadoCita::class,
            'estado_nuevo_id'
        );
    }
}