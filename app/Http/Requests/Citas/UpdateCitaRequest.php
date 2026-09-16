<?php

namespace App\Http\Requests\Citas;

use App\Models\Cita;
use App\Models\TratamientoCita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
                'nullable',
                'integer',
                'exists:estados_cita,id',
            ],

            /*
             * servicio_id no es una columna de citas.
             * El controller lo usa para actualizar servicios_cita.
             */
            'servicio_id' => [
                'required',
                'integer',
                'exists:servicios,id',
            ],

            'fecha_hora_inicio' => [
                'required',
                'date',
            ],

            /*
             * Laravel vuelve a calcular fecha_hora_fin usando
             * la duración real del servicio.
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
                'max:1000',
            ],
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {

        $validator->after(
            function (
                Validator $validator
            ) {

                $cita =
                    $this->route(
                        'cita'
                    );


                if (
                    !$cita instanceof Cita
                ) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | ¿LA CITA PERTENECE A UN TRATAMIENTO?
                |--------------------------------------------------------------------------
                */

                $relacion =
                    TratamientoCita::query()
                        ->with([
                            'tratamientoPaciente:id,paciente_id,servicio_id',
                        ])
                        ->where(
                            'cita_id',
                            $cita->id
                        )
                        ->first();


                if (
                    !$relacion
                ) {
                    return;
                }


                $tratamiento =
                    $relacion
                        ->tratamientoPaciente;


                if (
                    !$tratamiento
                ) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | PACIENTE PROTEGIDO
                |--------------------------------------------------------------------------
                |
                | Una sesión de un tratamiento no puede pasar a otro paciente.
                |
                */

                $pacienteEnviado =
                    (int)
                    $this->input(
                        'paciente_id'
                    );


                if (
                    $pacienteEnviado
                    !==
                    (int)
                    $tratamiento
                        ->paciente_id
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'paciente_id',
                            'No puedes cambiar el paciente porque esta cita pertenece a un tratamiento.'
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | SERVICIO PROTEGIDO
                |--------------------------------------------------------------------------
                |
                | La sesión siempre debe conservar el servicio del tratamiento.
                |
                */

                $servicioEnviado =
                    (int)
                    $this->input(
                        'servicio_id'
                    );


                if (
                    $servicioEnviado
                    !==
                    (int)
                    $tratamiento
                        ->servicio_id
                ) {

                    $validator
                        ->errors()
                        ->add(
                            'servicio_id',
                            'No puedes cambiar el servicio porque esta cita pertenece a un tratamiento.'
                        );
                }
            }
        );
    }

    public function messages(): array
    {
        return [

            'paciente_id.required' =>
                'Selecciona un paciente.',

            'paciente_id.exists' =>
                'El paciente seleccionado no existe.',

            'profesional_id.required' =>
                'Selecciona un profesional.',

            'profesional_id.exists' =>
                'El profesional seleccionado no existe.',

            'consultorio_id.exists' =>
                'El consultorio seleccionado no existe.',

            'estado_cita_id.exists' =>
                'El estado seleccionado no existe.',

            'servicio_id.required' =>
                'Selecciona un servicio.',

            'servicio_id.exists' =>
                'El servicio seleccionado no existe.',

            'fecha_hora_inicio.required' =>
                'Selecciona la fecha y hora de la cita.',

            'fecha_hora_inicio.date' =>
                'La fecha y hora seleccionada no es válida.',
        ];
    }
}
