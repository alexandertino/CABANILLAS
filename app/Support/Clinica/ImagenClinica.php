<?php

namespace App\Support\Clinica;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use RuntimeException;

final class ImagenClinica
{
    public static function guardar(
        UploadedFile $archivo,
        int $pacienteId,
        int $seguimientoId
    ): array {
        $mime = $archivo->getMimeType();

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',

            default => throw new RuntimeException(
                'Formato de imagen no permitido.'
            ),
        };

        $nombreBase = (string) Str::uuid();

        $base = sprintf(
            'seguimientos/paciente-%d/seguimiento-%d',
            $pacienteId,
            $seguimientoId
        );

        $rutaOriginal = sprintf(
            '%s/originales/%s.%s',
            $base,
            $nombreBase,
            $extension
        );

        $rutaVista = sprintf(
            '%s/visualizacion/%s.webp',
            $base,
            $nombreBase
        );

        $rutaMiniatura = sprintf(
            '%s/miniaturas/%s.webp',
            $base,
            $nombreBase
        );

        $disk = Storage::disk('local');

        $disk->makeDirectory(
            $base.'/originales'
        );

        $disk->makeDirectory(
            $base.'/visualizacion'
        );

        $disk->makeDirectory(
            $base.'/miniaturas'
        );

        $guardado = $disk->putFileAs(
            dirname($rutaOriginal),
            $archivo,
            basename($rutaOriginal)
        );

        if (!$guardado) {
            throw new RuntimeException(
                'No se pudo guardar la imagen clínica.'
            );
        }

        try {
            self::crearDerivados(
                $disk->path($rutaOriginal),
                $disk->path($rutaVista),
                $disk->path($rutaMiniatura)
            );
        } catch (\Throwable $e) {
            $disk->delete([
                $rutaOriginal,
                $rutaVista,
                $rutaMiniatura,
            ]);

            throw $e;
        }

        return [
            'ruta' => $rutaOriginal,
            'ruta_vista' => $rutaVista,
            'ruta_miniatura' =>
                $rutaMiniatura,
            'nombre_archivo' =>
                basename($rutaOriginal),
        ];
    }

    public static function rutasDerivadas(
        string $rutaOriginal
    ): array {
        $directorioSeguimiento =
            dirname(
                dirname($rutaOriginal)
            );

        $nombreBase = pathinfo(
            $rutaOriginal,
            PATHINFO_FILENAME
        );

        return [
            'vista' =>
                $directorioSeguimiento
                .'/visualizacion/'
                .$nombreBase
                .'.webp',

            'miniatura' =>
                $directorioSeguimiento
                .'/miniaturas/'
                .$nombreBase
                .'.webp',
        ];
    }

    public static function eliminar(
        string $rutaOriginal
    ): void {
        $rutas =
            self::rutasDerivadas(
                $rutaOriginal
            );

        Storage::disk('local')
            ->delete([
                $rutaOriginal,
                $rutas['vista'],
                $rutas['miniatura'],
            ]);
    }

    private static function crearDerivados(
        string $original,
        string $vista,
        string $miniatura
    ): void {
        if (
            !in_array(
                'WEBP',
                Imagick::queryFormats('WEBP'),
                true
            )
        ) {
            throw new RuntimeException(
                'Imagick no tiene soporte WebP.'
            );
        }

        $imagen = new Imagick(
            $original
        );

        /*
        |--------------------------------------------------------------------------
        | Solo trabajamos con el primer frame
        |--------------------------------------------------------------------------
        */

        $imagen->setIteratorIndex(0);

        /*
        |--------------------------------------------------------------------------
        | Corrige orientación EXIF antes de eliminar metadatos
        |--------------------------------------------------------------------------
        */

        if (
            method_exists(
                $imagen,
                'autoOrient'
            )
        ) {
            $imagen->autoOrient();
        }

        /*
        |--------------------------------------------------------------------------
        | La versión entregada al navegador no conserva EXIF/GPS
        |--------------------------------------------------------------------------
        */

        $imagen->stripImage();

        self::reducir(
            $imagen,
            2200
        );

        $imagen->setImageFormat(
            'webp'
        );

        $imagen->setImageCompressionQuality(
            90
        );

        if (
            !$imagen->writeImage(
                $vista
            )
        ) {
            throw new RuntimeException(
                'No se pudo crear la visualización clínica.'
            );
        }

        $mini = clone $imagen;

        self::reducir(
            $mini,
            480
        );

        $mini->setImageCompressionQuality(
            82
        );

        if (
            !$mini->writeImage(
                $miniatura
            )
        ) {
            $mini->clear();

            throw new RuntimeException(
                'No se pudo crear la miniatura clínica.'
            );
        }

        $mini->clear();
        $mini->destroy();

        $imagen->clear();
        $imagen->destroy();
    }

    private static function reducir(
        Imagick $imagen,
        int $maximo
    ): void {
        $ancho =
            $imagen->getImageWidth();

        $alto =
            $imagen->getImageHeight();

        if (
            $ancho <= $maximo
            &&
            $alto <= $maximo
        ) {
            return;
        }

        $imagen->thumbnailImage(
            $maximo,
            $maximo,
            true,
            true
        );
    }
}
