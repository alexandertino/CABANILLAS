<?php

namespace App\Providers;

use App\Contracts\AgendaServiceInterface;
use App\Services\ApiAgendaService;
use App\Services\MockAgendaService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AgendaServiceInterface::class, function () {
            if (config('services.clinic_api.driver') === 'mock') {
                return new MockAgendaService();
            }

            return new ApiAgendaService(
                (string) config('services.clinic_api.url'),
                (string) config('services.clinic_api.key'),
                (int) config('services.clinic_api.timeout', 10),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
