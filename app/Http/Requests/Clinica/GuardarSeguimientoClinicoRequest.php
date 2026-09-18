<?php

namespace App\Http\Requests\Clinica;

use Illuminate\Foundation\Http\FormRequest;

class GuardarSeguimientoClinicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tratamiento_paciente_id' => [
                'nullable',
                'integer',
                'exists:tratamientos_pacientes,id',
            ],

            'cita_id' => [
                'nullable',
                'integer',
                'exists:citas,id',
            ],

            'fecha_seguimiento' => [
                'required',
                'date',
            ],

            'titulo' => [
                'required',
                'string',
                'max:150',
            ],

            'observaciones' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ];
    }
}