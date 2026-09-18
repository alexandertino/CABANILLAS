<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Auditoria;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()
                ->where(
                    'email',
                    $request->string(Fortify::username())
                        ->trim()
                        ->lower()
                        ->toString()
                )
                ->first();

            $passwordValida = $user !== null
                && Hash::check(
                    (string) $request->input('password'),
                    $user->password
                );

            if (
                $user !== null
                && $user->activo !== true
                && $passwordValida
            ) {
                Auditoria::registrar(
                    'autenticacion',
                    'login_bloqueado',
                    'Intento de acceso de usuario inactivo',
                    $user,
                    propiedades: [
                        'motivo' => 'usuario_inactivo',
                    ],
                    anonimo: true
                );
            }

            return $user?->activo === true && $passwordValida
                ? $user
                : null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
