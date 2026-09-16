<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TratamientoCita extends Model
{
    protected $table =
        'tratamiento_citas';


    protected $fillable = [

        'tratamiento_paciente_id',

        'cita_id',

        'numero_sesion',

        'observaciones',

    ];


    public function tratamientoPaciente(): BelongsTo
    {
        return $this->belongsTo(
            TratamientoPaciente::class,
            'tratamiento_paciente_id'
        );
    }


    public function cita(): BelongsTo
    {
        return $this->belongsTo(
            Cita::class,
            'cita_id'
        );
    }
}