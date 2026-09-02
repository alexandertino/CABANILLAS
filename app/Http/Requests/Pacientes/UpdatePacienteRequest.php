<?php

namespace App\Http\Requests\Pacientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $paciente = $this->route('paciente');

        return [
            'tipo_documento' => [
                'required',
                'string',
                'max:20',
            ],

            'numero_documento' => [
                'required',
                'string',
                'max:30',
                Rule::unique('pacientes', 'numero_documento')
                    ->ignore($paciente?->id),
            ],

            'nombres' => [
                'required',
                'string',
                'max:120',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:120',
            ],

            'fecha_nacimiento' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:30',
            ],

            'correo' => [
                'nullable',
                'email',
                'max:150',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'nombre_contacto_emergencia' => [
                'nullable',
                'string',
                'max:150',
            ],

            'telefono_contacto_emergencia' => [
                'nullable',
                'string',
                'max:30',
            ],

            'observaciones' => [
                'nullable',
                'string',
            ],

            'activo' => [
                'boolean',
            ],
        ];
    }
}