<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Consultorio;
use App\Models\EstadoCita;
use App\Models\ServicioCita;
use App\Models\HistorialEstadoCita;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cita extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'cita_origen_id',
        'paciente_id',
        'profesional_id',
        'consultorio_id',
        'estado_cita_id',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'fecha_hora_fin_real',
        'motivo',
        'observaciones',
        'fecha_cancelacion',
        'motivo_cancelacion',
        'usuario_creador_id',
    ];
    public function citaOrigen()
    {
        return $this->belongsTo(
            Cita::class,
            'cita_origen_id'
        );
    }
    
    public function citasReprogramadas()
    {
        return $this->hasMany(
            Cita::class,
            'cita_origen_id'
        );
    }

    protected function casts(): array
    {
        return [

            'fecha_hora_inicio' =>
                'datetime',

            'fecha_hora_fin' =>
                'datetime',

            'fecha_hora_fin_real' =>
                'datetime',

            'fecha_cancelacion' =>
                'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class);
    }

    public function consultorio(): BelongsTo
    {
        return $this->belongsTo(Consultorio::class);
    }

    public function estadoCita(): BelongsTo
    {
        return $this->belongsTo(EstadoCita::class);
    }

    public function usuarioCreador(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_creador_id'
        );
    }

    public function serviciosCita(): HasMany
    {
        return $this->hasMany(ServicioCita::class);
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(HistorialEstadoCita::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function totalCita(): float
    {
        return (float) $this
            ->serviciosCita()
            ->sum('total');
    }

    public function totalPagado(): float
    {
        return (float) $this
            ->pagos()
            ->where(
                'estado',
                Pago::ESTADO_REGISTRADO
            )
            ->sum('monto');
    }

    public function saldoPendiente(): float
    {
        return max(
            $this->totalCita()
            -
            $this->totalPagado(),
            0
        );
    }

    public function estadoPago(): string
    {
        $total =
            $this->totalCita();

        $pagado =
            $this->totalPagado();


        if (
            $pagado <= 0
        ) {
            return 'PENDIENTE';
        }


        if (
            $pagado < $total
        ) {
            return 'PARCIAL';
        }


        return 'PAGADO';
    }

    public function tratamientoCita(): HasOne
    {
        return $this->hasOne(
            TratamientoCita::class,
            'cita_id'
        );
    }

}