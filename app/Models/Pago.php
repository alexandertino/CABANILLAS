<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'paciente_id',
        'cita_id',
        'metodo_pago_id',
        'monto',
        'fecha_pago',
        'referencia',
        'observaciones',
        'usuario_recibio_id',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_pago' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class);
    }

    public function usuarioRecibio(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_recibio_id'
        );
    }
}