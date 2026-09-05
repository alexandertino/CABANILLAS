<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClinicApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = (string) config('services.clinic_api.key');
        $providedKey = (string) $request->header('X-API-Key');

        if ($configuredKey === '') {
            return response()->json([
                'message' => 'La API no está configurada para aceptar solicitudes.',
            ], 503);
        }

        if ($providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'message' => 'Credenciales de API inválidas.',
            ], 401);
        }

        return $next($request);
    }
}
