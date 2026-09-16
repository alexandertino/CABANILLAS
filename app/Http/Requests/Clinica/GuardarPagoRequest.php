<?php

namespace App\Http\Requests\Clinica;

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

            'cita_id' => [
                'required',
                'integer',
                'exists:citas,id',
            ],

            'metodo_pago_id' => [
                'required',
                'integer',

                Rule::exists(
                    'metodos_pago',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'activo',
                            true
                        )
                ),
            ],

            'monto' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'fecha_pago' => [
                'nullable',
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

            'cita_id.required' =>
                'Selecciona una cita.',

            'cita_id.exists' =>
                'La cita seleccionada no existe.',

            'metodo_pago_id.required' =>
                'Selecciona un método de pago.',

            'metodo_pago_id.exists' =>
                'El método de pago seleccionado no está disponible.',

            'monto.required' =>
                'Ingresa el monto del pago.',

            'monto.numeric' =>
                'El monto debe ser un número válido.',

            'monto.gt' =>
                'El monto debe ser mayor a cero.',

            'fecha_pago.date' =>
                'La fecha del pago no es válida.',

            'numero_operacion.max' =>
                'El número de operación es demasiado largo.',

            'observaciones.max' =>
                'Las observaciones no pueden superar los 1000 caracteres.',

        ];
    }
}