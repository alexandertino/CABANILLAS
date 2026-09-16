<?php

namespace App\Http\Requests\Usuarios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    private const ROLES = [
        'ADMINISTRADOR',
        'RECEPCIONISTA',
        'ODONTOLOGO',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.crear') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
            'rol' => [
                'required',
                'string',
                Rule::in(self::ROLES),
            ],
            'profesional_id' => [
                'exclude_unless:rol,ODONTOLOGO',
                Rule::requiredIf(
                    fn () => $this->input('rol') === 'ODONTOLOGO'
                ),
                'nullable',
                'integer',
                Rule::exists('profesionales', 'id')
                    ->where(
                        fn ($query) => $query->where('activo', true)
                    ),
                Rule::unique('users', 'profesional_id'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(
                trim((string) $this->input('email'))
            ),
            'profesional_id' => $this->input('profesional_id') ?: null,
        ]);
    }

    public function messages(): array
    {
        return [
            'rol.in' => 'El rol seleccionado no es válido.',
            'profesional_id.required' =>
                'Selecciona el profesional asociado al odontólogo.',
            'profesional_id.exists' =>
                'El profesional seleccionado no existe o está inactivo.',
            'profesional_id.unique' =>
                'El profesional seleccionado ya está vinculado a otro usuario.',
        ];
    }
}
