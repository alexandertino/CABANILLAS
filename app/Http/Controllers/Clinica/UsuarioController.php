<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuarios\StoreUsuarioRequest;
use App\Http\Requests\Usuarios\UpdateUsuarioRequest;
use App\Models\Profesional;
use App\Models\User;
use App\Support\Auditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    private const ROLES = [
        'ADMINISTRADOR',
        'RECEPCIONISTA',
        'ODONTOLOGO',
    ];

    public function index(Request $request): Response
    {
        $buscar = $request->string('buscar')
            ->trim()
            ->toString();

        $rol = $request->string('rol', 'todos')
            ->toString();

        $estado = $request->string('estado', 'todos')
            ->toString();

        $usuarios = User::query()
            ->with([
                'roles:id,name',
                'profesional:id,nombres,apellidos',
            ])
            ->when($buscar, function ($query, $buscar) {
                $query->where(function ($query) use ($buscar) {
                    $query
                        ->where('name', 'ilike', "%{$buscar}%")
                        ->orWhere('email', 'ilike', "%{$buscar}%");
                });
            })
            ->when(
                in_array($rol, self::ROLES, true),
                fn ($query) => $query->whereHas(
                    'roles',
                    fn ($query) => $query
                        ->where('roles.name', $rol)
                        ->where('roles.guard_name', 'web')
                )
            )
            ->when(
                $estado === 'activos',
                fn ($query) => $query->where('activo', true)
            )
            ->when(
                $estado === 'inactivos',
                fn ($query) => $query->where('activo', false)
            )
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $usuariosPagina = $usuarios->getCollection()
            ->pluck('id');

        $columnasProfesional = [
            'id',
            'nombres',
            'apellidos',
        ];

        $profesionalesActuales = Profesional::query()
            ->select($columnasProfesional)
            ->where('activo', true)
            ->whereHas(
                'usuario',
                fn ($query) => $query->whereIn(
                    'users.id',
                    $usuariosPagina
                )
            )
            ->with('usuario:id,profesional_id')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $profesionalesLibres = Profesional::query()
            ->select($columnasProfesional)
            ->where('activo', true)
            ->whereDoesntHave('usuario')
            ->with('usuario:id,profesional_id')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->limit(100)
            ->get();

        $profesionales = $profesionalesActuales
            ->concat($profesionalesLibres)
            ->unique('id')
            ->sortBy('apellidos')
            ->values()
            ->map(fn (Profesional $profesional) => [
                'id' => $profesional->id,
                'nombres' => $profesional->nombres,
                'apellidos' => $profesional->apellidos,
                'usuario_id' => $profesional->usuario?->id,
            ]);

        $usuarios->through(fn (User $usuario) => [
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'activo' => $usuario->activo,
            'rol' => $usuario->roles->first()?->name,
            'profesional_id' => $usuario->profesional_id,
            'profesional' => $usuario->profesional
                ? [
                    'id' => $usuario->profesional->id,
                    'nombres' => $usuario->profesional->nombres,
                    'apellidos' => $usuario->profesional->apellidos,
                ]
                : null,
        ]);

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios,
            'profesionales' => $profesionales,
            'roles' => self::ROLES,
            'filtros' => [
                'buscar' => $buscar,
                'rol' => $rol,
                'estado' => $estado,
            ],
        ]);
    }

    public function store(
        StoreUsuarioRequest $request
    ): RedirectResponse {
        $datos = $request->validated();
        $rol = $datos['rol'];

        unset($datos['rol']);

        $datos['profesional_id'] = $rol === 'ODONTOLOGO'
            ? $datos['profesional_id']
            : null;

        DB::transaction(function () use ($datos, $rol): void {
            $usuario = User::create($datos);
            $usuario->syncRoles([$rol]);

            Auditoria::registrar(
                'usuarios',
                'crear',
                'Usuario creado',
                $usuario,
                despues: array_merge(
                    Auditoria::atributos(
                        $usuario,
                        Auditoria::USUARIO
                    ),
                    ['rol' => $rol]
                )
            );
        });

        return back()->with(
            'success',
            'Usuario registrado correctamente.'
        );
    }

    public function update(
        UpdateUsuarioRequest $request,
        User $usuario
    ): RedirectResponse {
        $datos = $request->validated();
        $rol = $datos['rol'];
        $passwordActualizada = filled(
            $datos['password'] ?? null
        );

        unset($datos['rol']);

        if (blank($datos['password'] ?? null)) {
            unset($datos['password']);
        }

        $datos['profesional_id'] = $rol === 'ODONTOLOGO'
            ? $datos['profesional_id']
            : null;

        DB::transaction(function () use (
            $usuario,
            $datos,
            $rol,
            $passwordActualizada
        ): void {
            $this->bloquearUsuariosActivos();

            $usuario = User::query()
                ->lockForUpdate()
                ->findOrFail($usuario->getKey());

            if (
                $rol !== 'ADMINISTRADOR'
                && $this->esUltimoAdministradorActivo($usuario)
            ) {
                throw ValidationException::withMessages([
                    'rol' =>
                        'El último administrador activo debe conservar ese rol.',
                ]);
            }

            $antes = array_merge(
                Auditoria::atributos(
                    $usuario,
                    Auditoria::USUARIO
                ),
                ['rol' => $usuario->roles()->value('name')]
            );

            $usuario->update($datos);
            $usuario->syncRoles([$rol]);

            Auditoria::registrar(
                'usuarios',
                'editar',
                'Usuario editado',
                $usuario,
                $antes,
                array_merge(
                    Auditoria::atributos(
                        $usuario->refresh(),
                        Auditoria::USUARIO
                    ),
                    ['rol' => $rol]
                ),
                [
                    'credencial_actualizada' => $passwordActualizada,
                ]
            );
        });

        return back()->with(
            'success',
            'Usuario actualizado correctamente.'
        );
    }

    public function desactivar(User $usuario): RedirectResponse
    {
        DB::transaction(function () use ($usuario): void {
            $this->bloquearUsuariosActivos();

            $usuario = User::query()
                ->lockForUpdate()
                ->findOrFail($usuario->getKey());

            if ($this->esUltimoAdministradorActivo($usuario)) {
                throw ValidationException::withMessages([
                    'usuario' =>
                        'No puedes desactivar al último administrador activo.',
                ]);
            }

            $antes = Auditoria::atributos(
                $usuario,
                Auditoria::USUARIO
            );

            $usuario->update([
                'activo' => false,
            ]);

            Auditoria::registrar(
                'usuarios',
                'desactivar',
                'Usuario desactivado',
                $usuario,
                $antes,
                Auditoria::atributos(
                    $usuario->refresh(),
                    Auditoria::USUARIO
                )
            );
        });

        return back()->with(
            'success',
            'Usuario desactivado correctamente.'
        );
    }

    public function reactivar(User $usuario): RedirectResponse
    {
        $antes = Auditoria::atributos(
            $usuario,
            Auditoria::USUARIO
        );

        $usuario->update([
            'activo' => true,
        ]);

        Auditoria::registrar(
            'usuarios',
            'activar',
            'Usuario activado',
            $usuario,
            $antes,
            Auditoria::atributos(
                $usuario->refresh(),
                Auditoria::USUARIO
            )
        );

        return back()->with(
            'success',
            'Usuario reactivado correctamente.'
        );
    }

    private function bloquearUsuariosActivos(): void
    {
        User::query()
            ->where('activo', true)
            ->lockForUpdate()
            ->get(['id']);
    }

    private function esUltimoAdministradorActivo(
        User $usuario
    ): bool {
        if (
            ! $usuario->activo
            || ! $usuario->hasRole('ADMINISTRADOR')
        ) {
            return false;
        }

        return User::role('ADMINISTRADOR', 'web')
            ->where('users.activo', true)
            ->where('users.id', '<>', $usuario->getKey())
            ->doesntExist();
    }
}
