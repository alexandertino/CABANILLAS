<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_origen' => [
                'required',
                Rule::in([
                    'CITA_SIMPLE',
                    'TRATAMIENTO',
                ]),
            ],

            'cita_id' => [
                'nullable',
                'integer',
                'exists:citas,id',
                Rule::requiredIf(
                    fn () =>
                        $this->input('tipo_origen')
                        === 'CITA_SIMPLE'
                ),
            ],

            'tratamiento_paciente_id' => [
                'nullable',
                'integer',
                'exists:tratamientos_pacientes,id',
                Rule::requiredIf(
                    fn () =>
                        $this->input('tipo_origen')
                        === 'TRATAMIENTO'
                ),
            ],

            'metodo_pago_id' => [
                'required',
                'integer',
                Rule::exists(
                    'metodos_pago',
                    'id'
                )->where(
                    'activo',
                    true
                ),
            ],

            'monto' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],

            'fecha_pago' => [
                'required',
                'date',
            ],

            'numero_operacion' => [
                'nullable',
                'string',
                'max:100',
            ],

            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_origen.required' =>
                'Selecciona qué deuda se pagará.',

            'cita_id.required' =>
                'Selecciona la cita que se pagará.',

            'tratamiento_paciente_id.required' =>
                'Selecciona el tratamiento que se pagará.',

            'metodo_pago_id.required' =>
                'Selecciona un método de pago.',

            'monto.required' =>
                'Ingresa el monto del pago.',

            'monto.gt' =>
                'El monto debe ser mayor a cero.',

            'fecha_pago.required' =>
                'Selecciona la fecha del pago.',
        ];
    }
}
