<?php

namespace App\Http\Requests\MetodosPago;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMetodoPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $metodoPago = $this->route('metodo_pago');

        return [
            'codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('metodos_pago', 'codigo')
                    ->ignore($metodoPago?->id),
            ],

            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('metodos_pago', 'nombre')
                    ->ignore($metodoPago?->id),
            ],

            'activo' => [
                'boolean',
            ],
        ];
    }
}