<?php

namespace App\Http\Requests\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    private const ROLES = [
        'ADMINISTRADOR',
        'RECEPCIONISTA',
        'ODONTOLOGO',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('usuarios.editar') ?? false;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        $usuarioId = $usuario instanceof User
            ? $usuario->getKey()
            : $usuario;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($usuarioId),
            ],
            'password' => [
                'nullable',
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
                Rule::unique('users', 'profesional_id')
                    ->ignore($usuarioId),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $password = $this->input('password');

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => mb_strtolower(
                trim((string) $this->input('email'))
            ),
            'password' => filled($password) ? $password : null,
            'password_confirmation' => filled($password)
                ? $this->input('password_confirmation')
                : null,
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
