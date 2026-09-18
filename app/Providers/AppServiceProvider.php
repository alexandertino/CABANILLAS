<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Auditoria;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Event::listen(Login::class, function (Login $evento): void {
            if (! $evento->user instanceof User) {
                return;
            }

            Auditoria::registrar(
                'autenticacion',
                'login',
                'Inicio de sesión exitoso',
                $evento->user,
                causante: $evento->user
            );
        });

        Event::listen(Logout::class, function (Logout $evento): void {
            if (! $evento->user instanceof User) {
                return;
            }

            Auditoria::registrar(
                'autenticacion',
                'logout',
                'Cierre de sesión',
                $evento->user,
                causante: $evento->user
            );
        });
    }
}
