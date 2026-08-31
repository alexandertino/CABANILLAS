<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioCita extends Model
{
    protected $table = 'servicios_cita';

    protected $fillable = [
        'cita_id',
        'servicio_id',
        'cantidad',
        'precio_unitario',
        'descuento',
        'total',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'descuento' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}