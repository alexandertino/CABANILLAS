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

class Cita extends Model
{
    protected $table = 'citas';

    protected $fillable = [
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
}