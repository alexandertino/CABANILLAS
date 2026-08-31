<?php

namespace App\Http\Requests\Citas;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paciente_id' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],

            'profesional_id' => [
                'required',
                'integer',
                'exists:profesionales,id',
            ],

            'consultorio_id' => [
                'nullable',
                'integer',
                'exists:consultorios,id',
            ],

            'estado_cita_id' => [
                'required',
                'integer',
                'exists:estados_cita,id',
            ],

            'fecha_hora_inicio' => [
                'required',
                'date',
            ],

            'fecha_hora_fin' => [
                'required',
                'date',
                'after:fecha_hora_inicio',
            ],

            'motivo' => [
                'nullable',
                'string',
                'max:255',
            ],

            'observaciones' => [
                'nullable',
                'string',
            ],

            'fecha_cancelacion' => [
                'nullable',
                'date',
            ],

            'motivo_cancelacion' => [
                'nullable',
                'string',
            ],
        ];
    }
}