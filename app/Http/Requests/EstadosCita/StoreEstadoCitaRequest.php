<?php

namespace App\Http\Requests\EstadosCita;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstadoCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => [
                'required',
                'string',
                'max:30',
                'unique:estados_cita,codigo',
            ],

            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:estados_cita,nombre',
            ],

            'es_final' => [
                'boolean',
            ],

            'activo' => [
                'boolean',
            ],
        ];
    }
}