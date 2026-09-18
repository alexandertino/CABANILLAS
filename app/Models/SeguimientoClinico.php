<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeguimientoClinico extends Model
{
    protected $table =
        'seguimientos_clinicos';

    protected $fillable = [
        'paciente_id',
        'tratamiento_paciente_id',
        'cita_id',
        'profesional_id',
        'usuario_creador_id',
        'fecha_seguimiento',
        'titulo',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_seguimiento' =>
                'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(
            Paciente::class
        );
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(
            TratamientoPaciente::class,
            'tratamiento_paciente_id'
        );
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(
            Cita::class
        );
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(
            Profesional::class
        );
    }

    public function usuarioCreador(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_creador_id'
        );
    }

    public function imagenes(): HasMany
    {
        return $this
            ->hasMany(
                SeguimientoImagen::class
            )
            ->orderBy('orden')
            ->orderBy('id');
    }
}