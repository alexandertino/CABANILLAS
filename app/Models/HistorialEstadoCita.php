<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEstadoCita extends Model
{
    protected $table = 'historial_estados_cita';

    public $timestamps = false;

    protected $fillable = [
        'cita_id',
        'estado_anterior_id',
        'estado_nuevo_id',
        'usuario_id',
        'motivo',
        'fecha_cambio',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cambio' => 'datetime',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function estadoAnterior(): BelongsTo
    {
        return $this->belongsTo(
            EstadoCita::class,
            'estado_anterior_id'
        );
    }

    public function estadoNuevo(): BelongsTo
    {
        return $this->belongsTo(
            EstadoCita::class,
            'estado_nuevo_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}