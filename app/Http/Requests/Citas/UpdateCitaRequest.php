<?php

namespace App\Http\Requests\Citas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

                Rule::exists(
                    'pacientes',
                    'id'
                )->where(
                    'activo',
                    true
                ),
            ],


            'profesional_id' => [
                'required',
                'integer',

                Rule::exists(
                    'profesionales',
                    'id'
                )->where(
                    'activo',
                    true
                ),
            ],


            'consultorio_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'consultorios',
                    'id'
                )->where(
                    'activo',
                    true
                ),
            ],


            'servicio_id' => [
                'required',
                'integer',

                Rule::exists(
                    'servicios',
                    'id'
                )->where(
                    'activo',
                    true
                ),
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
                'nullable',
                'date',
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
        ];
    }


    public function messages(): array
    {
        return [

            'paciente_id.required' =>
                'Selecciona un paciente.',

            'profesional_id.required' =>
                'Selecciona un profesional.',

            'servicio_id.required' =>
                'Selecciona un servicio.',

            'fecha_hora_inicio.required' =>
                'Selecciona la fecha y hora de la cita.',
        ];
    }
}