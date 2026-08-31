<?php

namespace App\Http\Requests\Servicios;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicioRequest extends FormRequest
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
                'unique:servicios,codigo',
            ],

            'nombre' => [
                'required',
                'string',
                'max:150',
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],

            'duracion_estimada_minutos' => [
                'required',
                'integer',
                'min:1',
            ],

            'precio_actual' => [
                'required',
                'numeric',
                'min:0',
            ],

            'activo' => [
                'boolean',
            ],
        ];
    }
}