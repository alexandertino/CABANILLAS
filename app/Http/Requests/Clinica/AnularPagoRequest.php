<?php

namespace App\Http\Requests\Clinica;

use Illuminate\Foundation\Http\FormRequest;

class AnularPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [

            'motivo_anulacion' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],

        ];
    }


    public function messages(): array
    {
        return [

            'motivo_anulacion.required' =>
                'Indica el motivo de la anulación.',

            'motivo_anulacion.min' =>
                'El motivo debe tener al menos 3 caracteres.',

            'motivo_anulacion.max' =>
                'El motivo no puede superar los 500 caracteres.',

        ];
    }
}