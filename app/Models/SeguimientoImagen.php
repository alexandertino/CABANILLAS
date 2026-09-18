<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeguimientoImagen extends Model
{
    protected $table =
        'seguimiento_imagenes';

    protected $fillable = [
        'seguimiento_clinico_id',
        'usuario_subio_id',
        'ruta',
        'nombre_original',
        'nombre_archivo',
        'mime_type',
        'tamanio_bytes',
        'ancho',
        'alto',
        'orden',
        'descripcion',
    ];

    protected $hidden = [
        'ruta',
        'nombre_archivo',
    ];

    protected $appends = [
        'url',
        'miniatura_url',
    ];

    protected function casts(): array
    {
        return [
            'tamanio_bytes' =>
                'integer',

            'ancho' =>
                'integer',

            'alto' =>
                'integer',

            'orden' =>
                'integer',
        ];
    }

    public function seguimiento(): BelongsTo
    {
        return $this->belongsTo(
            SeguimientoClinico::class,
            'seguimiento_clinico_id'
        );
    }

    public function usuarioSubio(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_subio_id'
        );
    }

    public function getUrlAttribute(): string
    {
        return route(
            'clinica.seguimiento-imagenes.ver',
            [
                'imagen' => $this->getKey(),
            ],
            false
        );
    }

    public function getMiniaturaUrlAttribute(): string
    {
        return route(
            'clinica.seguimiento-imagenes.miniatura',
            [
                'imagen' => $this->getKey(),
            ],
            false
        );
    }
}

