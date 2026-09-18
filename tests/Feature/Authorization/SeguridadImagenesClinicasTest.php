<?php

namespace Tests\Feature\Authorization;

use App\Models\SeguimientoClinico;
use App\Models\SeguimientoImagen;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SeguridadImagenesClinicasTest extends TestCase
{
    use DatabaseTransactions;

    private User $doctorA;
    private User $doctorB;
    private User $administrador;
    private User $recepcionista;

    private SeguimientoClinico $seguimientoA;
    private SeguimientoClinico $seguimientoB;

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Almacenamientos falsos
        |--------------------------------------------------------------------------
        |
        | Los tests nunca escriben fotografías reales en el proyecto.
        |
        */

        Storage::fake('local');
        Storage::fake('public');

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        $this->seed(
            RolesPermisosSeeder::class
        );

        $this->crearEscenario();
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | SUBIDA
    |--------------------------------------------------------------------------
    */

    public function test_odontologo_propietario_puede_subir_imagen_clinica_privada(): void
    {
        $archivo = UploadedFile::fake()
            ->image(
                'control-frontal.jpg',
                1200,
                800
            )
            ->size(700);

        $respuesta = $this
            ->actingAs($this->doctorA)
            ->withHeader(
                'Accept',
                'application/json'
            )
            ->post(
                "/clinica/seguimientos/{$this->seguimientoA->id}/imagenes",
                [
                    'imagen' => $archivo,
                    'descripcion' =>
                        'Vista frontal del control.',
                ]
            );

        $respuesta
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Imagen clínica agregada correctamente.'
            );

        $imagen = SeguimientoImagen::query()
            ->where(
                'seguimiento_clinico_id',
                $this->seguimientoA->id
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $this->doctorA->id,
            (int) $imagen->usuario_subio_id
        );

        $this->assertSame(
            'control-frontal.jpg',
            $imagen->nombre_original
        );

        $this->assertSame(
            'image/jpeg',
            $imagen->mime_type
        );

        $this->assertSame(
            'Vista frontal del control.',
            $imagen->descripcion
        );

        /*
        |--------------------------------------------------------------------------
        | Original
        |--------------------------------------------------------------------------
        */

        Storage::disk('local')
            ->assertExists(
                $imagen->ruta
            );

        $directorio = dirname(
            dirname(
                $imagen->ruta
            )
        );

        $nombreBase = pathinfo(
            $imagen->ruta,
            PATHINFO_FILENAME
        );

        $rutaVista =
            $directorio
            . '/visualizacion/'
            . $nombreBase
            . '.webp';

        $rutaMiniatura =
            $directorio
            . '/miniaturas/'
            . $nombreBase
            . '.webp';

        Storage::disk('local')
            ->assertExists(
                $rutaVista
            );

        Storage::disk('local')
            ->assertExists(
                $rutaMiniatura
            );

        /*
        |--------------------------------------------------------------------------
        | Nunca debe escribirse en public
        |--------------------------------------------------------------------------
        */

        Storage::disk('public')
            ->assertMissing(
                $imagen->ruta
            );

        Storage::disk('public')
            ->assertMissing(
                $rutaVista
            );

        Storage::disk('public')
            ->assertMissing(
                $rutaMiniatura
            );
    }

    /*
    |--------------------------------------------------------------------------
    | VISUALIZACIÓN
    |--------------------------------------------------------------------------
    */

    public function test_odontologo_propietario_puede_ver_miniatura_y_visualizacion(): void
    {
        $imagen = $this->crearImagenGuardada(
            $this->seguimientoA,
            $this->doctorA
        );

        $this
            ->actingAs($this->doctorA)
            ->get(
                "/clinica/seguimiento-imagenes/{$imagen->id}/miniatura"
            )
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'image/webp'
            );

        $this
            ->actingAs($this->doctorA)
            ->get(
                "/clinica/seguimiento-imagenes/{$imagen->id}/ver"
            )
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'image/webp'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | AISLAMIENTO ENTRE ODONTÓLOGOS
    |--------------------------------------------------------------------------
    */

    public function test_otro_odontologo_no_puede_ver_imagen_ajena_aunque_conozca_la_url(): void
    {
        $imagen = $this->crearImagenGuardada(
            $this->seguimientoA,
            $this->doctorA
        );

        $this
            ->actingAs($this->doctorB)
            ->get(
                "/clinica/seguimiento-imagenes/{$imagen->id}/ver"
            )
            ->assertForbidden();

        $this
            ->actingAs($this->doctorB)
            ->get(
                "/clinica/seguimiento-imagenes/{$imagen->id}/miniatura"
            )
            ->assertForbidden();

        $this
            ->actingAs($this->doctorB)
            ->delete(
                "/clinica/seguimiento-imagenes/{$imagen->id}"
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'seguimiento_imagenes',
            [
                'id' => $imagen->id,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRACIÓN Y RECEPCIÓN
    |--------------------------------------------------------------------------
    */

    public function test_administrador_y_recepcionista_no_pueden_ver_fotografias_clinicas(): void
    {
        $imagen = $this->crearImagenGuardada(
            $this->seguimientoA,
            $this->doctorA
        );

        foreach (
            [
                $this->administrador,
                $this->recepcionista,
            ] as $usuario
        ) {
            $this
                ->actingAs($usuario)
                ->get(
                    "/clinica/seguimiento-imagenes/{$imagen->id}/ver"
                )
                ->assertForbidden();

            $this
                ->actingAs($usuario)
                ->get(
                    "/clinica/seguimiento-imagenes/{$imagen->id}/miniatura"
                )
                ->assertForbidden();

            $this
                ->actingAs($usuario)
                ->delete(
                    "/clinica/seguimiento-imagenes/{$imagen->id}"
                )
                ->assertForbidden();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SUBIDA A SEGUIMIENTO AJENO
    |--------------------------------------------------------------------------
    */

    public function test_otro_odontologo_no_puede_subir_imagen_a_seguimiento_ajeno(): void
    {
        $archivo = UploadedFile::fake()
            ->image(
                'intento.jpg',
                800,
                600
            );

        $this
            ->actingAs($this->doctorB)
            ->withHeader(
                'Accept',
                'application/json'
            )
            ->post(
                "/clinica/seguimientos/{$this->seguimientoA->id}/imagenes",
                [
                    'imagen' => $archivo,
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'seguimiento_imagenes',
            [
                'seguimiento_clinico_id' =>
                    $this->seguimientoA->id,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDACIÓN DE TIPO
    |--------------------------------------------------------------------------
    */

    public function test_archivo_que_no_es_imagen_es_rechazado(): void
    {
        $archivo = UploadedFile::fake()
            ->create(
                'documento.txt',
                10,
                'text/plain'
            );

        $this
            ->actingAs($this->doctorA)
            ->withHeader(
                'Accept',
                'application/json'
            )
            ->post(
                "/clinica/seguimientos/{$this->seguimientoA->id}/imagenes",
                [
                    'imagen' => $archivo,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'imagen'
            );

        $this->assertDatabaseMissing(
            'seguimiento_imagenes',
            [
                'seguimiento_clinico_id' =>
                    $this->seguimientoA->id,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDACIÓN DE TAMAÑO
    |--------------------------------------------------------------------------
    */

    public function test_imagen_que_supera_15_mb_es_rechazada(): void
    {
        /*
         * Laravel expresa max:15360 en KB.
         * 15361 KB debe ser rechazado.
         */

        $archivo = UploadedFile::fake()
            ->image(
                'gigante.jpg',
                800,
                600
            )
            ->size(15361);

        $this
            ->actingAs($this->doctorA)
            ->withHeader(
                'Accept',
                'application/json'
            )
            ->post(
                "/clinica/seguimientos/{$this->seguimientoA->id}/imagenes",
                [
                    'imagen' => $archivo,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'imagen'
            );

        $this->assertDatabaseMissing(
            'seguimiento_imagenes',
            [
                'seguimiento_clinico_id' =>
                    $this->seguimientoA->id,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINACIÓN
    |--------------------------------------------------------------------------
    */

    public function test_eliminar_imagen_borra_registro_y_los_tres_archivos_privados(): void
    {
        $imagen = $this->crearImagenGuardada(
            $this->seguimientoA,
            $this->doctorA
        );

        $rutaOriginal = $imagen->ruta;

        $directorio = dirname(
            dirname(
                $rutaOriginal
            )
        );

        $nombreBase = pathinfo(
            $rutaOriginal,
            PATHINFO_FILENAME
        );

        $rutaVista =
            $directorio
            . '/visualizacion/'
            . $nombreBase
            . '.webp';

        $rutaMiniatura =
            $directorio
            . '/miniaturas/'
            . $nombreBase
            . '.webp';

        Storage::disk('local')
            ->assertExists(
                $rutaOriginal
            );

        Storage::disk('local')
            ->assertExists(
                $rutaVista
            );

        Storage::disk('local')
            ->assertExists(
                $rutaMiniatura
            );

        $this
            ->actingAs($this->doctorA)
            ->withHeader(
                'Accept',
                'application/json'
            )
            ->delete(
                "/clinica/seguimiento-imagenes/{$imagen->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Imagen clínica eliminada correctamente.'
            );

        $this->assertDatabaseMissing(
            'seguimiento_imagenes',
            [
                'id' => $imagen->id,
            ]
        );

        Storage::disk('local')
            ->assertMissing(
                $rutaOriginal
            );

        Storage::disk('local')
            ->assertMissing(
                $rutaVista
            );

        Storage::disk('local')
            ->assertMissing(
                $rutaMiniatura
            );
    }

    /*
    |--------------------------------------------------------------------------
    | AUDITORÍA AL SUBIR
    |--------------------------------------------------------------------------
    */

    public function test_subir_imagen_genera_auditoria_sin_guardar_archivo_en_el_log(): void
    {
        $archivo = UploadedFile::fake()
            ->image(
                'prueba-auditoria.jpg',
                1000,
                700
            );

        $this
            ->actingAs($this->doctorA)
            ->withHeader(
                'Accept',
                'application/json'
            )
            ->post(
                "/clinica/seguimientos/{$this->seguimientoA->id}/imagenes",
                [
                    'imagen' => $archivo,
                    'descripcion' =>
                        'Descripción clínica privada de prueba.',
                ]
            )
            ->assertCreated();

        $actividad = Activity::query()
            ->where(
                'log_name',
                'seguimientos'
            )
            ->where(
                'event',
                'subir_imagen'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $this->doctorA->id,
            (int) $actividad->causer_id
        );

        $this->assertSame(
            $this->seguimientoA->id,
            (int) $actividad->subject_id
        );

        $propiedades =
            $actividad
                ->properties
                ->toArray();

        $this->assertSame(
            $this->seguimientoA->id,
            (int) $propiedades[
                'seguimiento_id'
            ]
        );

        $this->assertSame(
            8111,
            (int) $propiedades[
                'paciente_id'
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | El log no debe guardar contenido clínico sensible
        |--------------------------------------------------------------------------
        */

        $json = json_encode(
            $propiedades,
            JSON_UNESCAPED_UNICODE
        );

        $this->assertIsString(
            $json
        );

        $this->assertStringNotContainsString(
            'Descripción clínica privada de prueba.',
            $json
        );

        $this->assertStringNotContainsString(
            'prueba-auditoria.jpg',
            $json
        );

        $this->assertStringNotContainsString(
            'storage/app',
            $json
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AUDITORÍA AL ELIMINAR
    |--------------------------------------------------------------------------
    */

    public function test_eliminar_imagen_genera_auditoria(): void
    {
        $imagen = $this->crearImagenGuardada(
            $this->seguimientoA,
            $this->doctorA
        );

        $imagenId = $imagen->id;

        $this
            ->actingAs($this->doctorA)
            ->withHeader(
                'Accept',
                'application/json'
            )
            ->delete(
                "/clinica/seguimiento-imagenes/{$imagenId}"
            )
            ->assertOk();

        $actividad = Activity::query()
            ->where(
                'log_name',
                'seguimientos'
            )
            ->where(
                'event',
                'eliminar_imagen'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $this->doctorA->id,
            (int) $actividad->causer_id
        );

        $this->assertSame(
            $this->seguimientoA->id,
            (int) $actividad->subject_id
        );

        $this->assertSame(
            $imagenId,
            (int) $actividad
                ->properties
                ->get('imagen_id')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SERIALIZACIÓN
    |--------------------------------------------------------------------------
    */

    public function test_serializacion_no_expone_ruta_fisica_privada(): void
    {
        $imagen = $this->crearImagenGuardada(
            $this->seguimientoA,
            $this->doctorA
        );

        $datos = $imagen
            ->fresh()
            ->toArray();

        $this->assertArrayNotHasKey(
            'ruta',
            $datos
        );

        $this->assertArrayNotHasKey(
            'nombre_archivo',
            $datos
        );

        $this->assertArrayHasKey(
            'url',
            $datos
        );

        $this->assertArrayHasKey(
            'miniatura_url',
            $datos
        );

        $this->assertStringContainsString(
            "/clinica/seguimiento-imagenes/{$imagen->id}/ver",
            $datos['url']
        );

        $this->assertStringContainsString(
            "/clinica/seguimiento-imagenes/{$imagen->id}/miniatura",
            $datos['miniatura_url']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ESCENARIO
    |--------------------------------------------------------------------------
    */

    private function crearEscenario(): void
    {
        $ahora = now();

        DB::table(
            'profesionales'
        )->insert([
            $this->profesional(
                8101,
                'A'
            ),
            $this->profesional(
                8102,
                'B'
            ),
        ]);

        $this->doctorA =
            $this->usuario(
                8141,
                'doctor-fotos-a@cabanillas.test',
                8101,
                'ODONTOLOGO'
            );

        $this->doctorB =
            $this->usuario(
                8142,
                'doctor-fotos-b@cabanillas.test',
                8102,
                'ODONTOLOGO'
            );

        $this->administrador =
            $this->usuario(
                8143,
                'admin-fotos@cabanillas.test',
                null,
                'ADMINISTRADOR'
            );

        $this->recepcionista =
            $this->usuario(
                8144,
                'recepcion-fotos@cabanillas.test',
                null,
                'RECEPCIONISTA'
            );

        DB::table(
            'pacientes'
        )->insert([
            $this->paciente(
                8111,
                'ALFA'
            ),
            $this->paciente(
                8112,
                'BETA'
            ),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Relación doctor ↔ paciente
        |--------------------------------------------------------------------------
        */

        DB::table(
            'estados_cita'
        )->insert([
            'id' => 8121,
            'codigo' => 'CONFIRMADA',
            'nombre' => 'Confirmada',
            'es_final' => false,
            'activo' => true,
        ]);

        DB::table(
            'citas'
        )->insert([
            [
                'id' => 8201,
                'paciente_id' => 8111,
                'profesional_id' => 8101,
                'estado_cita_id' => 8121,

                'fecha_hora_inicio' =>
                    $ahora
                        ->copy()
                        ->addDay(),

                'fecha_hora_fin' =>
                    $ahora
                        ->copy()
                        ->addDay()
                        ->addMinutes(30),

                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],

            [
                'id' => 8202,
                'paciente_id' => 8112,
                'profesional_id' => 8102,
                'estado_cita_id' => 8121,

                'fecha_hora_inicio' =>
                    $ahora
                        ->copy()
                        ->addDays(2),

                'fecha_hora_fin' =>
                    $ahora
                        ->copy()
                        ->addDays(2)
                        ->addMinutes(30),

                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Seguimiento A
        |--------------------------------------------------------------------------
        */

        $this->seguimientoA =
            SeguimientoClinico::create([
                'paciente_id' => 8111,

                'tratamiento_paciente_id' =>
                    null,

                'cita_id' => 8201,

                'profesional_id' =>
                    8101,

                'usuario_creador_id' =>
                    $this->doctorA->id,

                'fecha_seguimiento' =>
                    $ahora,

                'titulo' =>
                    'Seguimiento doctor A',

                'observaciones' =>
                    'Observación privada A.',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Seguimiento B
        |--------------------------------------------------------------------------
        */

        $this->seguimientoB =
            SeguimientoClinico::create([
                'paciente_id' => 8112,

                'tratamiento_paciente_id' =>
                    null,

                'cita_id' => 8202,

                'profesional_id' =>
                    8102,

                'usuario_creador_id' =>
                    $this->doctorB->id,

                'fecha_seguimiento' =>
                    $ahora,

                'titulo' =>
                    'Seguimiento doctor B',

                'observaciones' =>
                    'Observación privada B.',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | IMAGEN PRECARGADA
    |--------------------------------------------------------------------------
    */

    private function crearImagenGuardada(
        SeguimientoClinico $seguimiento,
        User $usuario
    ): SeguimientoImagen {
        $base =
            "seguimientos/paciente-{$seguimiento->paciente_id}"
            . "/seguimiento-{$seguimiento->id}";

        $nombreBase =
            'imagen-test-'
            . $seguimiento->id;

        $rutaOriginal =
            "{$base}/originales/{$nombreBase}.jpg";

        $rutaVista =
            "{$base}/visualizacion/{$nombreBase}.webp";

        $rutaMiniatura =
            "{$base}/miniaturas/{$nombreBase}.webp";

        Storage::disk('local')
            ->put(
                $rutaOriginal,
                'ORIGINAL-PRIVADO'
            );

        Storage::disk('local')
            ->put(
                $rutaVista,
                'WEBP-VISTA-PRIVADA'
            );

        Storage::disk('local')
            ->put(
                $rutaMiniatura,
                'WEBP-MINIATURA-PRIVADA'
            );

        return SeguimientoImagen::create([
            'seguimiento_clinico_id' =>
                $seguimiento->id,

            'usuario_subio_id' =>
                $usuario->id,

            'ruta' =>
                $rutaOriginal,

            'nombre_original' =>
                'imagen-test.jpg',

            'nombre_archivo' =>
                "{$nombreBase}.jpg",

            'mime_type' =>
                'image/jpeg',

            'tamanio_bytes' =>
                2048,

            'ancho' =>
                1200,

            'alto' =>
                800,

            'orden' =>
                1,

            'descripcion' =>
                'Imagen de prueba.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | USUARIO
    |--------------------------------------------------------------------------
    */

    private function usuario(
        int $id,
        string $email,
        ?int $profesionalId,
        string $rol
    ): User {
        $usuario =
            User::factory()->create([
                'id' => $id,

                'email' =>
                    $email,

                'profesional_id' =>
                    $profesionalId,

                'activo' =>
                    true,
            ]);

        $usuario->assignRole(
            $rol
        );

        return $usuario;
    }

    /*
    |--------------------------------------------------------------------------
    | PROFESIONAL
    |--------------------------------------------------------------------------
    */

    private function profesional(
        int $id,
        string $sufijo
    ): array {
        return [
            'id' =>
                $id,

            'nombres' =>
                "Doctor {$sufijo}",

            'apellidos' =>
                'Fotos',

            'numero_documento' =>
                "DOC-FOTO-{$sufijo}",

            'numero_colegiatura' =>
                "COP-FOTO-{$sufijo}",

            'activo' =>
                true,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PACIENTE
    |--------------------------------------------------------------------------
    */

    private function paciente(
        int $id,
        string $sufijo
    ): array {
        return [
            'id' =>
                $id,

            'codigo' =>
                "PAC-FOTO-{$sufijo}",

            'tipo_documento' =>
                'DNI',

            'numero_documento' =>
                "PAC-FOTO-DOC-{$sufijo}",

            'nombres' =>
                "Paciente {$sufijo}",

            'apellidos' =>
                'Fotos',

            'activo' =>
                true,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ];
    }
}