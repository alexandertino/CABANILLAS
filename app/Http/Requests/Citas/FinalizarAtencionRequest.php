<?php

namespace App\Http\Requests\Citas;

use App\Support\Clinica\AlcanceClinico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinalizarAtencionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $profesionalId = AlcanceClinico::profesionalId(
            $this->user()
        );

        if ($profesionalId !== null) {
            $this->merge([
                'profesional_id' => $profesionalId,
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | ACCIÓN DEL TRATAMIENTO
            |--------------------------------------------------------------------------
            */

            'accion_tratamiento' => [
                'nullable',

                Rule::in([
                    'PROGRAMAR_SIGUIENTE',
                    'CONTINUAR_SIN_FECHA',
                    'COMPLETAR',
                ]),
            ],


            /*
            |--------------------------------------------------------------------------
            | PRÓXIMA CITA
            |--------------------------------------------------------------------------
            */

            'fecha_hora_siguiente' => [

                'nullable',

                Rule::requiredIf(
                    fn () =>
                        $this->input('accion_tratamiento')
                        ===
                        'PROGRAMAR_SIGUIENTE'
                ),

                'date',

                'after:now',
            ],


            'profesional_id' => [

                'nullable',

                Rule::requiredIf(
                    fn () =>
                        $this->input('accion_tratamiento')
                        ===
                        'PROGRAMAR_SIGUIENTE'
                ),

                'integer',

                'exists:profesionales,id',
            ],


            'consultorio_id' => [
                'nullable',
                'integer',
                'exists:consultorios,id',
            ],


            'motivo_siguiente' => [
                'nullable',
                'string',
                'max:255',
            ],


            'observaciones_siguiente' => [
                'nullable',
                'string',
                'max:1000',
            ],

        ];
    }


    public function messages(): array
    {
        return [

            'accion_tratamiento.in' =>
                'La acción seleccionada para el tratamiento no es válida.',


            'fecha_hora_siguiente.required' =>
                'Selecciona la fecha y hora de la siguiente sesión.',

            'fecha_hora_siguiente.date' =>
                'La fecha de la siguiente sesión no es válida.',

            'fecha_hora_siguiente.after' =>
                'La siguiente sesión debe programarse para una fecha futura.',


            'profesional_id.required' =>
                'Selecciona el profesional de la siguiente sesión.',

            'profesional_id.exists' =>
                'El profesional seleccionado no existe.',


            'consultorio_id.exists' =>
                'El consultorio seleccionado no existe.',

        ];
    }
}
