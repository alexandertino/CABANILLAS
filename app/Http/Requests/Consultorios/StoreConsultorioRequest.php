<?php

namespace App\Http\Requests\Consultorios;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultorioRequest extends FormRequest
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
                'unique:consultorios,codigo',
            ],

            'nombre' => [
                'required',
                'string',
                'max:100',
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],

            'activo' => [
                'boolean',
            ],
        ];
    }
}