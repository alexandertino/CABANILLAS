<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultorio extends Model
{
    protected $table = 'consultorios';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
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