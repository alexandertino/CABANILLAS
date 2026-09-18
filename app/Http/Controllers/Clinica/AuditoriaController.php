<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'usuario' => ['nullable', 'integer', 'exists:users,id'],
            'modulo' => ['nullable', 'string', 'max:100'],
            'accion' => ['nullable', 'string', 'max:100'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        $buscar = trim((string) ($filtros['buscar'] ?? ''));

        $actividades = Activity::query()
            ->with('causer:id,name,email')
            ->when($buscar, function (Builder $query) use ($buscar): void {
                $query->where(function (Builder $query) use ($buscar): void {
                    $query
                        ->where('description', 'ilike', "%{$buscar}%")
                        ->orWhere('event', 'ilike', "%{$buscar}%")
                        ->orWhere('log_name', 'ilike', "%{$buscar}%")
                        ->orWhereRaw('CAST(subject_id AS TEXT) ILIKE ?', ["%{$buscar}%"])
                        ->orWhereHas('causer', function (Builder $query) use ($buscar): void {
                            $query
                                ->where('name', 'ilike', "%{$buscar}%")
                                ->orWhere('email', 'ilike', "%{$buscar}%");
                        });
                });
            })
            ->when(
                $filtros['usuario'] ?? null,
                fn (Builder $query, int|string $usuario) => $query
                    ->where('causer_type', User::class)
                    ->where('causer_id', $usuario)
            )
            ->when(
                $filtros['modulo'] ?? null,
                fn (Builder $query, string $modulo) => $query
                    ->where('log_name', $modulo)
            )
            ->when(
                $filtros['accion'] ?? null,
                fn (Builder $query, string $accion) => $query
                    ->where('event', $accion)
            )
            ->when(
                $filtros['desde'] ?? null,
                fn (Builder $query, string $desde) => $query
                    ->whereDate('created_at', '>=', $desde)
            )
            ->when(
                $filtros['hasta'] ?? null,
                fn (Builder $query, string $hasta) => $query
                    ->whereDate('created_at', '<=', $hasta)
            )
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Activity $actividad) => [
                'id' => $actividad->id,
                'modulo' => $actividad->log_name,
                'accion' => $actividad->event,
                'descripcion' => $actividad->description,
                'modelo' => $this->nombreModelo($actividad->subject_type),
                'registro_id' => $actividad->subject_id,
                'usuario' => $actividad->causer
                    ? [
                        'id' => $actividad->causer->id,
                        'name' => $actividad->causer->name,
                        'email' => $actividad->causer->email,
                    ]
                    : null,
                'cambios' => $actividad->attribute_changes?->toArray() ?? [],
                'propiedades' => $actividad->properties?->toArray() ?? [],
                'fecha' => $actividad->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Auditoria/Index', [
            'actividades' => $actividades,
            'usuarios' => User::query()
                ->whereIn(
                    'id',
                    Activity::query()
                        ->where('causer_type', User::class)
                        ->whereNotNull('causer_id')
                        ->select('causer_id')
                )
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'modulos' => Activity::query()
                ->whereNotNull('log_name')
                ->distinct()
                ->orderBy('log_name')
                ->pluck('log_name'),
            'acciones' => Activity::query()
                ->whereNotNull('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event'),
            'filtros' => array_merge([
                'buscar' => '',
                'usuario' => null,
                'modulo' => null,
                'accion' => null,
                'desde' => null,
                'hasta' => null,
            ], $filtros),
        ]);
    }

    private function nombreModelo(?string $tipo): ?string
    {
        if ($tipo === null) {
            return null;
        }

        return [
            'User' => 'Usuario',
            'Paciente' => 'Paciente',
            'Cita' => 'Cita',
            'TratamientoPaciente' => 'Tratamiento',
            'Pago' => 'Pago',
        ][class_basename($tipo)] ?? Str::headline(class_basename($tipo));
    }
}
