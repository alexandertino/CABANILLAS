<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';


    public $timestamps = false;


    protected $fillable = [

        'codigo',

        'nombre',

        'activo',

    ];


    protected $casts = [

        'activo' =>
            'boolean',

    ];


    public function pagos(): HasMany
    {
        return $this->hasMany(
            Pago::class,
            'metodo_pago_id'
        );
    }
}