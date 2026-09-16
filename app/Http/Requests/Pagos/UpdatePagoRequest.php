<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePagoRequest extends FormRequest
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
}