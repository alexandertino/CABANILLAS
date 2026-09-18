<?php

namespace App\Support;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\Contracts\Activity;

final class Auditoria
{
    public const USUARIO = [
        'name',
        'email',
        'activo',
        'profesional_id',
    ];

    public const PACIENTE = [
        'codigo',
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'telefono',
        'correo',
        'direccion',
        'activo',
    ];

    public const CITA = [
        'cita_origen_id',
        'paciente_id',
        'profesional_id',
        'consultorio_id',
        'estado_cita_id',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'fecha_hora_fin_real',
        'fecha_cancelacion',
    ];

    public const TRATAMIENTO = [
        'paciente_id',
        'servicio_id',
        'profesional_id',
        'precio_acordado',
        'estado',
        'fecha_inicio',
        'fecha_fin',
    ];

    public const SEGUIMIENTO = [
        'paciente_id',
        'tratamiento_paciente_id',
        'cita_id',
        'profesional_id',
        'usuario_creador_id',
        'fecha_seguimiento',
        'titulo',
    ];

    public const PAGO = [
        'paciente_id',
        'cita_id',
        'tratamiento_paciente_id',
        'metodo_pago_id',
        'monto',
        'fecha_pago',
        'numero_operacion',
        'estado',
        'usuario_registro_id',
        'fecha_anulacion',
        'usuario_anulacion_id',
    ];

    private const CAMPOS_SENSIBLES = [
        'password',
        'password_confirmation',
        'remember_token',
        'cookie',
        'cookies',
        'token',
        'access_token',
        'refresh_token',
        'session',
        'sesion',
        'secret',
    ];

    public static function atributos(
        Model $modelo,
        array $campos
    ): array {
        return collect($campos)
            ->mapWithKeys(fn (string $campo) => [
                $campo => self::normalizar(
                    $modelo->getAttribute($campo)
                ),
            ])
            ->all();
    }

    public static function registrar(
        string $modulo,
        string $accion,
        string $descripcion,
        ?Model $sujeto = null,
        array $antes = [],
        array $despues = [],
        array $propiedades = [],
        ?User $causante = null,
        bool $anonimo = false
    ): ?Activity {
        [$antes, $despues] = self::soloCambios(
            self::sanitizar($antes),
            self::sanitizar($despues)
        );

        $request = app()->bound('request')
            ? request()
            : null;

        $logger = activity($modulo)
            ->event($accion)
            ->withChanges([
                'old' => $antes,
                'new' => $despues,
            ])
            ->withProperties(self::sanitizar(array_merge(
                $propiedades,
                [
                    'ip' => $request?->ip(),
                    'user_agent' => Str::limit(
                        (string) $request?->userAgent(),
                        1000,
                        ''
                    ),
                ]
            )));

        if ($sujeto !== null) {
            $logger->performedOn($sujeto);
        }

        if ($anonimo) {
            $logger->causedByAnonymous();
        } else {
            $logger->causedBy(
                $causante ?? Auth::user()
            );
        }

        return $logger->log($descripcion);
    }

    private static function soloCambios(
        array $antes,
        array $despues
    ): array {
        if ($antes === [] || $despues === []) {
            return [$antes, $despues];
        }

        $claves = array_values(array_unique(array_merge(
            array_keys($antes),
            array_keys($despues)
        )));

        $clavesCambiadas = array_filter(
            $claves,
            fn (string $clave) => Arr::get($antes, $clave)
                !== Arr::get($despues, $clave)
        );

        return [
            Arr::only($antes, $clavesCambiadas),
            Arr::only($despues, $clavesCambiadas),
        ];
    }

    private static function sanitizar(array $datos): array
    {
        $resultado = [];

        foreach ($datos as $clave => $valor) {
            $claveNormalizada = Str::lower((string) $clave);

            if (
                in_array($claveNormalizada, self::CAMPOS_SENSIBLES, true)
                || Str::contains($claveNormalizada, [
                    'password',
                    'token',
                    'secret',
                    'cookie',
                    'session',
                    'remember',
                ])
            ) {
                continue;
            }

            $resultado[$clave] = is_array($valor)
                ? self::sanitizar($valor)
                : self::normalizar($valor);
        }

        return $resultado;
    }

    private static function normalizar(mixed $valor): mixed
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }

        if ($valor instanceof \Stringable) {
            return (string) $valor;
        }

        return $valor;
    }
}
