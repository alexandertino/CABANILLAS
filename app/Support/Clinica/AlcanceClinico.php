<?php

namespace App\Support\Clinica;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\TratamientoPaciente;
use App\Models\User;
use App\Models\SeguimientoClinico;
use Illuminate\Database\Eloquent\Builder;

final class AlcanceClinico
{
    public static function citas(
        Builder $query,
        User $usuario
    ): Builder {
        $profesionalId = self::profesionalId($usuario);

        if ($profesionalId === null) {
            return $query;
        }

        return $query->where(
            'citas.profesional_id',
            $profesionalId
        );
    }

    public static function pacientes(
        Builder $query,
        User $usuario
    ): Builder {
        $profesionalId = self::profesionalId($usuario);

        if ($profesionalId === null) {
            return $query;
        }

        return $query->where(
            function (Builder $query) use ($profesionalId): void {
                $query
                    ->whereHas(
                        'citas',
                        fn (Builder $query) => $query->where(
                            'citas.profesional_id',
                            $profesionalId
                        )
                    )
                    ->orWhereHas(
                        'tratamientos',
                        function (Builder $query) use ($profesionalId): void {
                            $query
                                ->where(
                                    'tratamientos_pacientes.profesional_id',
                                    $profesionalId
                                )
                                ->orWhereHas(
                                    'citas',
                                    fn (Builder $query) => $query->where(
                                        'citas.profesional_id',
                                        $profesionalId
                                    )
                                );
                        }
                    );
            }
        );
    }

    public static function tratamientos(
        Builder $query,
        User $usuario
    ): Builder {
        $profesionalId = self::profesionalId($usuario);

        if ($profesionalId === null) {
            return $query;
        }

        return $query->where(
            function (Builder $query) use ($profesionalId): void {
                $query
                    ->where(
                        'tratamientos_pacientes.profesional_id',
                        $profesionalId
                    )
                    ->orWhereHas(
                        'citas',
                        fn (Builder $query) => $query->where(
                            'citas.profesional_id',
                            $profesionalId
                        )
                    );
            }
        );
    }

    public static function autorizarCita(
        User $usuario,
        Cita $cita
    ): void {
        $profesionalId = self::profesionalId($usuario);

        if ($profesionalId === null) {
            return;
        }

        abort_unless(
            (int) $cita->profesional_id === $profesionalId,
            403,
            'No tienes acceso a esta cita.'
        );
    }

    public static function autorizarPaciente(
        User $usuario,
        Paciente $paciente
    ): void {
        if (!self::esOdontologo($usuario)) {
            return;
        }

        $autorizado = self::pacientes(
            Paciente::query()->whereKey($paciente->getKey()),
            $usuario
        )->exists();

        abort_unless(
            $autorizado,
            403,
            'No tienes acceso a este paciente.'
        );
    }

    public static function autorizarTratamiento(
        User $usuario,
        TratamientoPaciente $tratamiento
    ): void {
        if (!self::esOdontologo($usuario)) {
            return;
        }

        $autorizado = self::tratamientos(
            TratamientoPaciente::query()
                ->whereKey($tratamiento->getKey()),
            $usuario
        )->exists();

        abort_unless(
            $autorizado,
            403,
            'No tienes acceso a este tratamiento.'
        );
    }

    public static function seguimientos(
        Builder $query,
        User $usuario
    ): Builder {
        abort_unless(
            self::esOdontologo($usuario),
            403,
            'Solo un odontólogo puede acceder al seguimiento clínico.'
        );

        $profesionalId =
            self::profesionalId($usuario);

        return $query->where(
            'seguimientos_clinicos.profesional_id',
            $profesionalId
        );
    }

    public static function autorizarSeguimiento(
        User $usuario,
        SeguimientoClinico $seguimiento
    ): void {
        abort_unless(
            self::esOdontologo($usuario),
            403,
            'No tienes acceso al seguimiento clínico.'
        );

        $autorizado = self::seguimientos(
            SeguimientoClinico::query()
                ->whereKey($seguimiento->getKey()),
            $usuario
        )->exists();

        abort_unless(
            $autorizado,
            403,
            'No tienes acceso a este seguimiento clínico.'
        );
    }
    
    public static function profesionalId(
        User $usuario
    ): ?int {
        if (!self::esOdontologo($usuario)) {
            return null;
        }

        abort_if(
            $usuario->profesional_id === null,
            403,
            'Tu cuenta de odontólogo no tiene un profesional asociado.'
        );

        return (int) $usuario->profesional_id;
    }

    private static function esOdontologo(
        User $usuario
    ): bool {
        return $usuario->hasRole('ODONTOLOGO');
    }
}

