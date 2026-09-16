<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $fillable = [
        'codigo',
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'telefono',
        'correo',
        'direccion',
        'nombre_contacto_emergencia',
        'telefono_contacto_emergencia',
        'observaciones',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function tratamientos(): HasMany
    {
        return $this->hasMany(
            TratamientoPaciente::class,
            'paciente_id'
        );
    }

}