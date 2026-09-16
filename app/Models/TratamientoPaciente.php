<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TratamientoPaciente extends Model
{
    protected $table = 'tratamientos_pacientes';

    public const ESTADO_PLANIFICADO = 'PLANIFICADO';
    public const ESTADO_EN_PROCESO = 'EN_PROCESO';
    public const ESTADO_COMPLETADO = 'COMPLETADO';
    public const ESTADO_CANCELADO = 'CANCELADO';

    protected $fillable = [
        'paciente_id',
        'servicio_id',
        'profesional_id',
        'precio_acordado',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
    ];

    protected $casts = [
        'precio_acordado' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(
            Paciente::class,
            'paciente_id'
        );
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(
            Servicio::class,
            'servicio_id'
        );
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(
            Profesional::class,
            'profesional_id'
        );
    }

    public function tratamientoCitas(): HasMany
    {
        return $this->hasMany(
            TratamientoCita::class,
            'tratamiento_paciente_id'
        );
    }

    public function citas(): BelongsToMany
    {
        return $this->belongsToMany(
            Cita::class,
            'tratamiento_citas',
            'tratamiento_paciente_id',
            'cita_id'
        )
            ->withPivot([
                'numero_sesion',
                'observaciones',
            ])
            ->withTimestamps();
    }

    /**
     * Pagos ya asociados directamente al tratamiento.
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(
            Pago::class,
            'tratamiento_paciente_id'
        );
    }

    /**
     * Consulta de pagos que deben contar para este tratamiento.
     *
     * Incluye:
     * 1) pagos nuevos con tratamiento_paciente_id;
     * 2) pagos antiguos que todavía solo tienen cita_id, siempre que
     *    esa cita pertenezca a este tratamiento.
     *
     * Esto permite que el monto "Pagado" no aparezca como S/ 0
     * mientras terminamos de migrar PagoController a tratamientos.
     */
    public function pagosContabilizables(): Builder
    {
        $citaIds = $this
            ->tratamientoCitas()
            ->pluck('cita_id');

        return Pago::query()
            ->where(
                'estado',
                Pago::ESTADO_REGISTRADO
            )
            ->where(
                'paciente_id',
                $this->paciente_id
            )
            ->where(
                function (Builder $query) use ($citaIds) {

                    $query->where(
                        'tratamiento_paciente_id',
                        $this->id
                    );

                    if ($citaIds->isNotEmpty()) {
                        $query->orWhereIn(
                            'cita_id',
                            $citaIds
                        );
                    }
                }
            );
    }

    public function totalTratamiento(): float
    {
        return (float) $this->precio_acordado;
    }

    public function totalPagado(): float
    {
        return (float) $this
            ->pagosContabilizables()
            ->sum('monto');
    }

    public function saldoPendiente(): float
    {
        return max(
            $this->totalTratamiento()
            -
            $this->totalPagado(),
            0
        );
    }


/**
 * Total de filas de tratamiento_citas.
 *
 * Incluye sesiones atendidas, canceladas y no asistidas.
 * Una reprogramación conserva la misma fila de tratamiento_citas,
 * por lo que no duplica el número de sesión.
 */
public function sesionesProgramadasCount(): int
{
    return $this
        ->tratamientoCitas()
        ->count();
}

/**
 * Solo una cita ATENDIDA cuenta como sesión clínica realizada.
 */
public function sesionesRealizadasCount(): int
{
    return $this
        ->tratamientoCitas()
        ->whereHas(
            'cita.estadoCita',
            function (Builder $query) {
                $query->where(
                    'codigo',
                    'ATENDIDA'
                );
            }
        )
        ->count();
}

public function sesionesNoAsistioCount(): int
{
    return $this
        ->tratamientoCitas()
        ->whereHas(
            'cita.estadoCita',
            function (Builder $query) {
                $query->where(
                    'codigo',
                    'NO_ASISTIO'
                );
            }
        )
        ->count();
}

public function sesionesCanceladasCount(): int
{
    return $this
        ->tratamientoCitas()
        ->whereHas(
            'cita.estadoCita',
            function (Builder $query) {
                $query->where(
                    'codigo',
                    'CANCELADA'
                );
            }
        )
        ->count();
}

public function sesionesActivasCount(): int
{
    return $this
        ->tratamientoCitas()
        ->whereHas(
            'cita.estadoCita',
            function (Builder $query) {
                $query->whereIn(
                    'codigo',
                    [
                        'PENDIENTE',
                        'CONFIRMADA',
                        'EN_ATENCION',
                    ]
                );
            }
        )
        ->count();
}

public function resumenSesiones(): array
{
    return [
        'programadas' =>
            $this->sesionesProgramadasCount(),

        'realizadas' =>
            $this->sesionesRealizadasCount(),

        'no_asistio' =>
            $this->sesionesNoAsistioCount(),

        'canceladas' =>
            $this->sesionesCanceladasCount(),

        'activas' =>
            $this->sesionesActivasCount(),
    ];
}

    public function estadoPago(): string
    {
        $total = $this->totalTratamiento();
        $pagado = $this->totalPagado();

        if ($pagado <= 0) {
            return 'PENDIENTE';
        }

        if ($pagado < $total) {
            return 'PARCIAL';
        }

        return 'PAGADO';
    }
}
