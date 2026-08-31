<?php

namespace App\Http\Requests\MetodosPago;

use Illuminate\Foundation\Http\FormRequest;

class StoreMetodoPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => [
                'required',
                'string',
                'max:30',
                'unique:metodos_pago,codigo',
            ],

            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:metodos_pago,nombre',
            ],

            'activo' => [
                'boolean',
            ],
        ];
    }
}