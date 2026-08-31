<?php

namespace App\Http\Requests\Consultorios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsultorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $consultorio = $this->route('consultorio');

        return [
            'codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('consultorios', 'codigo')
                    ->ignore($consultorio?->id),
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