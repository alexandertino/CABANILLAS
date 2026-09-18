<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinica\SubirSeguimientoImagenRequest;
use App\Models\SeguimientoClinico;
use App\Models\SeguimientoImagen;
use App\Models\User;
use App\Support\Auditoria;
use App\Support\Clinica\AlcanceClinico;
use App\Support\Clinica\ImagenClinica;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SeguimientoImagenController extends Controller
{
    public function store(
        SubirSeguimientoImagenRequest $request,
        SeguimientoClinico $seguimiento
    ): JsonResponse {
        /** @var User $usuario */
        $usuario = $request->user();

        AlcanceClinico::autorizarSeguimiento(
            $usuario,
            $seguimiento
        );

        $archivo =
            $request->file('imagen');

        $dimensiones =
            @getimagesize(
                $archivo->getRealPath()
            );

        if (!$dimensiones) {
            throw ValidationException::withMessages([
                'imagen' =>
                    'No se pudo leer la imagen.',
            ]);
        }

        $ancho =
            (int) $dimensiones[0];

        $alto =
            (int) $dimensiones[1];

        /*
        |--------------------------------------------------------------------------
        | Protección ante imágenes exageradamente grandes
        |--------------------------------------------------------------------------
        |
        | 80 megapíxeles permite fotografías de alta resolución,
        | pero evita procesar imágenes absurdamente grandes.
        |
        */

        if (
            ($ancho * $alto)
            > 80_000_000
        ) {
            throw ValidationException::withMessages([
                'imagen' =>
                    'La resolución de la imagen es demasiado grande.',
            ]);
        }

        $rutas =
            ImagenClinica::guardar(
                $archivo,
                (int) $seguimiento->paciente_id,
                (int) $seguimiento->id
            );

        try {
            $orden =
                (int) $seguimiento
                    ->imagenes()
                    ->max('orden')
                + 1;

            $imagen =
                SeguimientoImagen::create([
                    'seguimiento_clinico_id' =>
                        $seguimiento->id,

                    'usuario_subio_id' =>
                        $usuario->id,

                    'ruta' =>
                        $rutas['ruta'],

                    'nombre_original' =>
                        $archivo
                            ->getClientOriginalName(),

                    'nombre_archivo' =>
                        $rutas[
                            'nombre_archivo'
                        ],

                    'mime_type' =>
                        $archivo
                            ->getMimeType(),

                    'tamanio_bytes' =>
                        $archivo
                            ->getSize(),

                    'ancho' =>
                        $ancho,

                    'alto' =>
                        $alto,

                    'orden' =>
                        $orden,

                    'descripcion' =>
                        $request
                            ->validated(
                                'descripcion'
                            ),
                ]);
        } catch (\Throwable $e) {
            ImagenClinica::eliminar(
                $rutas['ruta']
            );

            throw $e;
        }

        Auditoria::registrar(
            modulo: 'seguimientos',
            accion: 'subir_imagen',
            descripcion:
                'Agregó una imagen al seguimiento clínico.',
            sujeto: $seguimiento,
            propiedades: [
                'imagen_id' =>
                    $imagen->id,

                'seguimiento_id' =>
                    $seguimiento->id,

                'paciente_id' =>
                    $seguimiento->paciente_id,

                'mime_type' =>
                    $imagen->mime_type,

                'tamanio_bytes' =>
                    $imagen->tamanio_bytes,

                'ancho' =>
                    $imagen->ancho,

                'alto' =>
                    $imagen->alto,
            ],
            causante: $usuario
        );

        return response()->json([
            'message' =>
                'Imagen clínica agregada correctamente.',

            'imagen' =>
                $imagen->fresh(),
        ], 201);
    }

    public function ver(
        SeguimientoImagen $imagen
    ): BinaryFileResponse {
        $this->autorizarImagen(
            $imagen
        );

        $rutas =
            ImagenClinica::rutasDerivadas(
                $imagen->ruta
            );

        return $this->respuestaPrivada(
            $rutas['vista'],
            'image/webp'
        );
    }

    public function miniatura(
        SeguimientoImagen $imagen
    ): BinaryFileResponse {
        $this->autorizarImagen(
            $imagen
        );

        $rutas =
            ImagenClinica::rutasDerivadas(
                $imagen->ruta
            );

        return $this->respuestaPrivada(
            $rutas['miniatura'],
            'image/webp'
        );
    }

    public function destroy(
        SeguimientoImagen $imagen
    ): JsonResponse {
        /** @var User $usuario */
        $usuario = request()->user();

        $seguimiento =
            $imagen->seguimiento()
                ->firstOrFail();

        AlcanceClinico::autorizarSeguimiento(
            $usuario,
            $seguimiento
        );

        $imagenId =
            $imagen->id;

        $ruta =
            $imagen->ruta;

        $imagen->delete();

        ImagenClinica::eliminar(
            $ruta
        );

        Auditoria::registrar(
            modulo: 'seguimientos',
            accion: 'eliminar_imagen',
            descripcion:
                'Eliminó una imagen del seguimiento clínico.',
            sujeto: $seguimiento,
            propiedades: [
                'imagen_id' =>
                    $imagenId,

                'seguimiento_id' =>
                    $seguimiento->id,

                'paciente_id' =>
                    $seguimiento->paciente_id,
            ],
            causante: $usuario
        );

        return response()->json([
            'message' =>
                'Imagen clínica eliminada correctamente.',
        ]);
    }

    private function autorizarImagen(
        SeguimientoImagen $imagen
    ): void {
        /** @var User $usuario */
        $usuario = request()->user();

        $seguimiento =
            $imagen->seguimiento()
                ->firstOrFail();

        AlcanceClinico::autorizarSeguimiento(
            $usuario,
            $seguimiento
        );
    }

    private function respuestaPrivada(
        string $ruta,
        string $mime
    ): BinaryFileResponse {
        $disk =
            Storage::disk('local');

        abort_unless(
            $disk->exists($ruta),
            404
        );

        return response()->file(
            $disk->path($ruta),
            [
                'Content-Type' =>
                    $mime,

                'Cache-Control' =>
                    'private, no-store, max-age=0',

                'Pragma' =>
                    'no-cache',

                'X-Content-Type-Options' =>
                    'nosniff',
            ]
        );
    }
}