<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paciente_id' => ['required', 'integer', Rule::exists('pacientes', 'id')->where('activo', true)],
            'profesional_id' => ['required', 'integer', Rule::exists('profesionales', 'id')->where('activo', true)],
            'servicio_id' => ['required', 'integer', Rule::exists('servicios', 'id')->where('activo', true)],
            'consultorio_id' => ['nullable', 'integer', Rule::exists('consultorios', 'id')->where('activo', true)],
            'fecha_hora_inicio' => ['required', 'date'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
