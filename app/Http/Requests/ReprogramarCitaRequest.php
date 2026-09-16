<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReprogramarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profesional_id' => [
                'required',
                'integer',
                Rule::exists('profesionales', 'id')
                    ->where('activo', true),
            ],

            'consultorio_id' => [
                'nullable',
                'integer',
                Rule::exists('consultorios', 'id')
                    ->where('activo', true),
            ],

            'fecha_hora_inicio' => [
                'required',
                'date',
            ],

            'motivo' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'profesional_id.required' =>
                'Selecciona un profesional.',

            'profesional_id.exists' =>
                'El profesional seleccionado no está disponible.',

            'consultorio_id.exists' =>
                'El consultorio seleccionado no está disponible.',

            'fecha_hora_inicio.required' =>
                'Selecciona la nueva fecha y hora.',

            'fecha_hora_inicio.date' =>
                'La fecha y hora seleccionada no es válida.',

            'motivo.max' =>
                'El motivo no puede superar los 500 caracteres.',
        ];
    }
}