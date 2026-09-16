<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;

class AnularPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo_anulacion' => [
                'required',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_anulacion.required' =>
                'Indica por qué se anula el pago.',
        ];
    }
}