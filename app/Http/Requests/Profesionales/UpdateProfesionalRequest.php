<?php

namespace App\Http\Requests\Profesionales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfesionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $profesional = $this->route('profesional');

        return [
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],

            'numero_documento' => [
                'required',
                'string',
                'max:30',
                Rule::unique('profesionales', 'numero_documento')
                    ->ignore($profesional?->id),
            ],

            'numero_colegiatura' => [
                'required',
                'string',
                'max:50',
                Rule::unique('profesionales', 'numero_colegiatura')
                    ->ignore($profesional?->id),
            ],

            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:150'],
            'activo' => ['boolean'],
        ];
    }
}