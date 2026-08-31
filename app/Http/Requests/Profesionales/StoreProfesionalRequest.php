<?php

namespace App\Http\Requests\Profesionales;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfesionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],

            'numero_documento' => [
                'required',
                'string',
                'max:30',
                'unique:profesionales,numero_documento',
            ],

            'numero_colegiatura' => [
                'required',
                'string',
                'max:50',
                'unique:profesionales,numero_colegiatura',
            ],

            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:150'],
            'activo' => ['boolean'],
        ];
    }
}