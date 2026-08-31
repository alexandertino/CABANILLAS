<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profesional extends Model
{
    protected $table = 'profesionales';

    protected $fillable = [
        'nombres',
        'apellidos',
        'numero_documento',
        'numero_colegiatura',
        'telefono',
        'correo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }
}