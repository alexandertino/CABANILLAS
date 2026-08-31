<?php

namespace App\Http\Requests\EstadosCita;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstadoCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $estadoCita = $this->route('estado_cita');

        return [
            'codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('estados_cita', 'codigo')
                    ->ignore($estadoCita?->id),
            ],

            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('estados_cita', 'nombre')
                    ->ignore($estadoCita?->id),
            ],

            'es_final' => [
                'boolean',
            ],

            'activo' => [
                'boolean',
            ],
        ];
    }
}