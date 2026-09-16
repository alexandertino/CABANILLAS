<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    public const ESTADO_REGISTRADO = 'REGISTRADO';
    public const ESTADO_ANULADO = 'ANULADO';

    protected $fillable = [
        'paciente_id',
        'cita_id',
        'tratamiento_paciente_id',
        'metodo_pago_id',
        'monto',
        'fecha_pago',
        'numero_operacion',
        'observaciones',
        'estado',
        'usuario_registro_id',
        'motivo_anulacion',
        'fecha_anulacion',
        'usuario_anulacion_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'datetime',
        'fecha_anulacion' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function tratamientoPaciente(): BelongsTo
    {
        return $this->belongsTo(
            TratamientoPaciente::class,
            'tratamiento_paciente_id'
        );
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    public function usuarioAnulacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_anulacion_id');
    }

    public function estaAnulado(): bool
    {
        return $this->estado === self::ESTADO_ANULADO;
    }
}
