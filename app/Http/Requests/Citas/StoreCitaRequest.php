<?php

namespace App\Http\Requests\Citas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                'nullable',
                'integer',
                'exists:estados_cita,id',
            ],


            'fecha_hora_inicio' => [
                'required',
                'date',
            ],


            /*
             * Vue la calcula para mostrarla,
             * pero Laravel vuelve a calcularla.
             */
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

            'paciente_id.exists' =>
                'El paciente seleccionado no está disponible.',


            'profesional_id.required' =>
                'Selecciona un profesional.',

            'profesional_id.exists' =>
                'El profesional seleccionado no está disponible.',


            'servicio_id.required' =>
                'Selecciona un servicio.',

            'servicio_id.exists' =>
                'El servicio seleccionado no está disponible.',


            'consultorio_id.exists' =>
                'El consultorio seleccionado no está disponible.',


            'fecha_hora_inicio.required' =>
                'Selecciona la fecha y hora de la cita.',

            'fecha_hora_inicio.date' =>
                'La fecha de inicio no es válida.',
        ];
    }
}