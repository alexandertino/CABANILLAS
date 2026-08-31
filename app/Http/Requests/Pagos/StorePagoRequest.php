<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
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

            'cita_id' => [
                'nullable',
                'integer',
                'exists:citas,id',
            ],

            'metodo_pago_id' => [
                'required',
                'integer',
                'exists:metodos_pago,id',
            ],

            'monto' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'fecha_pago' => [
                'required',
                'date',
            ],

            'referencia' => [
                'nullable',
                'string',
                'max:150',
            ],

            'observaciones' => [
                'nullable',
                'string',
            ],

            'estado' => [
                'required',
                'string',
                'max:30',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'paciente_id.required' => 'Debe seleccionar un paciente.',
            'metodo_pago_id.required' =>
                'Debe seleccionar un método de pago.',

            'monto.required' => 'Debe ingresar el monto del pago.',
            'monto.min' => 'El monto debe ser mayor a cero.',

            'fecha_pago.required' =>
                'Debe indicar la fecha del pago.',
        ];
    }
}