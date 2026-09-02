<?php

namespace App\Http\Requests\Pacientes;

use Illuminate\Foundation\Http\FormRequest;

class StorePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
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
                'unique:pacientes,numero_documento',
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

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del paciente es obligatorio.',
            'codigo.unique' => 'El código del paciente ya está registrado.',

            'tipo_documento.required' => 'El tipo de documento es obligatorio.',

            'numero_documento.required' => 'El número de documento es obligatorio.',
            'numero_documento.unique' => 'El número de documento ya está registrado.',

            'nombres.required' => 'Los nombres son obligatorios.',
            'apellidos.required' => 'Los apellidos son obligatorios.',

            'fecha_nacimiento.before_or_equal' =>
                'La fecha de nacimiento no puede ser posterior a hoy.',

            'correo.email' => 'El correo electrónico no tiene un formato válido.',
        ];
    }
}