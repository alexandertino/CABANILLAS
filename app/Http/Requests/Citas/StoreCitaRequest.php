<?php

namespace App\Http\Requests\Citas;

use Illuminate\Foundation\Http\FormRequest;

class StoreCitaRequest extends FormRequest
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

    public function messages(): array
    {
        return [
            'paciente_id.required' => 'Debe seleccionar un paciente.',
            'paciente_id.exists' => 'El paciente seleccionado no existe.',

            'profesional_id.required' =>
                'Debe seleccionar un profesional.',

            'profesional_id.exists' =>
                'El profesional seleccionado no existe.',

            'estado_cita_id.required' =>
                'Debe seleccionar un estado para la cita.',

            'fecha_hora_inicio.required' =>
                'Debe indicar la fecha y hora de inicio.',

            'fecha_hora_fin.required' =>
                'Debe indicar la fecha y hora de finalización.',

            'fecha_hora_fin.after' =>
                'La hora de finalización debe ser posterior a la hora de inicio.',
        ];
    }
}