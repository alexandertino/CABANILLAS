<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AislamientoDatosOdontologoTest extends TestCase
{
    use DatabaseTransactions;

    private User $doctorA;

    private User $doctorB;

    private User $administrador;

    private User $recepcionista;

    private Carbon $fecha;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesPermisosSeeder::class);

        $this->crearEscenarioClinico();
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        parent::tearDown();
    }

    public function test_cada_odontologo_solo_lista_sus_citas_y_el_backend_ignora_el_filtro_ajeno(): void
    {
        $respuestaA = $this->actingAs($this->doctorA)
            ->get('/clinica/citas?profesional=8102');

        $respuestaA->assertOk();
        $idsA = $this->idsInertia($respuestaA, 'citas.data');

        $this->assertContains(8201, $idsA);
        $this->assertContains(8203, $idsA);
        $this->assertNotContains(8202, $idsA);

        $respuestaB = $this->actingAs($this->doctorB)
            ->get('/clinica/citas?profesional=8101');

        $respuestaB->assertOk();
        $idsB = $this->idsInertia($respuestaB, 'citas.data');

        $this->assertContains(8202, $idsB);
        $this->assertNotContains(8201, $idsB);
        $this->assertNotContains(8203, $idsB);

        $agenda = $this->actingAs($this->doctorA)
            ->getJson('/clinica/citas/agenda?' . http_build_query([
                'fecha' => $this->fecha->format('Y-m-d'),
                'profesional_id' => 8102,
            ]))
            ->assertOk()
            ->json('citas');

        $idsAgenda = collect($agenda)->pluck('id')->all();

        $this->assertContains(8201, $idsAgenda);
        $this->assertContains(8203, $idsAgenda);
        $this->assertNotContains(8202, $idsAgenda);
    }

    public function test_un_odontologo_no_puede_operar_ni_usar_como_referencia_una_cita_ajena(): void
    {
        $this->actingAs($this->doctorA)
            ->patchJson('/clinica/citas/8202/estado', [
                'estado_cita_id' => 8122,
            ])
            ->assertForbidden();

        $this->actingAs($this->doctorB)
            ->patchJson('/clinica/citas/8201/estado', [
                'estado_cita_id' => 8122,
            ])
            ->assertForbidden();

        $this->actingAs($this->doctorA)
            ->getJson('/clinica/citas/disponibilidad?' . http_build_query([
                'fecha' => $this->fecha->format('Y-m-d'),
                'profesional_id' => 8102,
                'servicio_id' => 8131,
                'cita_id' => 8202,
            ]))
            ->assertForbidden();
    }

    public function test_cada_odontologo_solo_ve_los_pacientes_relacionados_con_su_actividad(): void
    {
        $respuestaA = $this->actingAs($this->doctorA)
            ->get('/clinica/pacientes');

        $respuestaA->assertOk();
        $this->assertSame(
            [8111],
            $this->idsInertia($respuestaA, 'pacientes.data')
        );

        $respuestaB = $this->actingAs($this->doctorB)
            ->get('/clinica/pacientes');

        $respuestaB->assertOk();
        $this->assertSame(
            [8112],
            $this->idsInertia($respuestaB, 'pacientes.data')
        );

        $this->actingAs($this->doctorA)
            ->getJson('/clinica/pacientes/buscar?q=Beta')
            ->assertOk()
            ->assertExactJson([]);

        $this->actingAs($this->doctorA)
            ->getJson('/clinica/pacientes/8112/tratamientos-activos')
            ->assertForbidden();
    }

    public function test_cada_odontologo_solo_consulta_sus_tratamientos_y_no_accede_por_id_a_uno_ajeno(): void
    {
        $tratamientosA = $this->actingAs($this->doctorA)
            ->getJson('/clinica/pacientes/8111/tratamientos-activos')
            ->assertOk()
            ->json('tratamientos');

        $this->assertSame(
            [8301],
            collect($tratamientosA)->pluck('id')->all()
        );

        $this->actingAs($this->doctorA)
            ->getJson('/clinica/tratamientos/8302')
            ->assertForbidden();

        $tratamientosB = $this->actingAs($this->doctorB)
            ->getJson('/clinica/pacientes/8112/tratamientos-activos')
            ->assertOk()
            ->json('tratamientos');

        $this->assertSame(
            [8302],
            collect($tratamientosB)->pluck('id')->all()
        );

        $this->actingAs($this->doctorB)
            ->getJson('/clinica/tratamientos/8301')
            ->assertForbidden();
    }

    public function test_administrador_y_recepcionista_conservan_la_visibilidad_completa(): void
    {
        foreach ([$this->administrador, $this->recepcionista] as $usuario) {
            $citas = $this->actingAs($usuario)->get('/clinica/citas');
            $citas->assertOk();

            $idsCitas = $this->idsInertia($citas, 'citas.data');
            $this->assertContains(8201, $idsCitas);
            $this->assertContains(8202, $idsCitas);

            $pacientes = $this->actingAs($usuario)->get('/clinica/pacientes');
            $pacientes->assertOk();

            $idsPacientes = $this->idsInertia($pacientes, 'pacientes.data');
            $this->assertContains(8111, $idsPacientes);
            $this->assertContains(8112, $idsPacientes);

            $this->actingAs($usuario)
                ->getJson('/clinica/tratamientos/8302')
                ->assertOk();
        }
    }

    public function test_el_odontologo_puede_iniciar_y_finalizar_una_cita_propia(): void
    {
        $this->actingAs($this->doctorA)
            ->patch('/clinica/citas/8203/estado', [
                'estado_cita_id' => 8122,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('citas', [
            'id' => 8203,
            'estado_cita_id' => 8122,
        ]);

        $this->actingAs($this->doctorA)
            ->patch('/clinica/citas/8203/finalizar-atencion')
            ->assertRedirect();

        $this->assertDatabaseHas('citas', [
            'id' => 8203,
            'estado_cita_id' => 8123,
        ]);
    }

    public function test_un_odontologo_sin_profesional_asociado_recibe_403(): void
    {
        $odontologo = User::factory()->create([
            'email' => 'odontologo-sin-profesional@cabanillas.test',
            'profesional_id' => null,
        ]);
        $odontologo->assignRole('ODONTOLOGO');

        $this->actingAs($odontologo)
            ->get('/clinica/citas')
            ->assertForbidden();

        $this->actingAs($odontologo)
            ->get('/clinica/pacientes')
            ->assertForbidden();

        $this->actingAs($odontologo)
            ->getJson('/clinica/tratamientos/8301')
            ->assertForbidden();
    }

    private function crearEscenarioClinico(): void
    {
        $ahora = now();
        $this->fecha = $ahora->copy()->addDay()->setTime(10, 0);

        DB::table('profesionales')->insert([
            $this->profesional(8101, 'A'),
            $this->profesional(8102, 'B'),
        ]);

        $this->doctorA = $this->usuario(8141, 'doctor-a@cabanillas.test', 8101, 'ODONTOLOGO');
        $this->doctorB = $this->usuario(8142, 'doctor-b@cabanillas.test', 8102, 'ODONTOLOGO');
        $this->administrador = $this->usuario(8143, 'admin-aislamiento@cabanillas.test', null, 'ADMINISTRADOR');
        $this->recepcionista = $this->usuario(8144, 'recepcion-aislamiento@cabanillas.test', null, 'RECEPCIONISTA');

        DB::table('pacientes')->insert([
            $this->paciente(8111, 'ALFA'),
            $this->paciente(8112, 'BETA'),
        ]);

        DB::table('estados_cita')->insert([
            ['id' => 8121, 'codigo' => 'CONFIRMADA', 'nombre' => 'Confirmada', 'es_final' => false, 'activo' => true],
            ['id' => 8122, 'codigo' => 'EN_ATENCION', 'nombre' => 'En atención', 'es_final' => false, 'activo' => true],
            ['id' => 8123, 'codigo' => 'ATENDIDA', 'nombre' => 'Atendida', 'es_final' => true, 'activo' => true],
        ]);

        DB::table('servicios')->insert([
            'id' => 8131,
            'codigo' => 'SERV-AISLAMIENTO',
            'nombre' => 'Servicio aislamiento',
            'duracion_estimada_minutos' => 30,
            'precio_actual' => 100,
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        DB::table('citas')->insert([
            $this->cita(8201, 8111, 8101, 0),
            $this->cita(8202, 8112, 8102, 60),
            $this->cita(8203, 8111, 8101, 120),
        ]);

        DB::table('tratamientos_pacientes')->insert([
            $this->tratamiento(8301, 8111, 8101),
            $this->tratamiento(8302, 8112, 8102),
        ]);

        DB::table('tratamiento_citas')->insert([
            $this->tratamientoCita(8401, 8301, 8201),
            $this->tratamientoCita(8402, 8302, 8202),
        ]);
    }

    private function usuario(int $id, string $email, ?int $profesionalId, string $rol): User
    {
        $usuario = User::factory()->create([
            'id' => $id,
            'email' => $email,
            'profesional_id' => $profesionalId,
            'activo' => true,
        ]);
        $usuario->assignRole($rol);

        return $usuario;
    }

    private function profesional(int $id, string $sufijo): array
    {
        return [
            'id' => $id,
            'nombres' => "Doctor {$sufijo}",
            'apellidos' => 'Aislamiento',
            'numero_documento' => "DOC-AIS-{$sufijo}",
            'numero_colegiatura' => "COP-AIS-{$sufijo}",
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function paciente(int $id, string $sufijo): array
    {
        return [
            'id' => $id,
            'codigo' => "PAC-AIS-{$sufijo}",
            'tipo_documento' => 'DNI',
            'numero_documento' => "PAC-DOC-{$sufijo}",
            'nombres' => "Paciente {$sufijo}",
            'apellidos' => 'Aislamiento',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function cita(int $id, int $pacienteId, int $profesionalId, int $minutos): array
    {
        $inicio = $this->fecha->copy()->addMinutes($minutos);

        return [
            'id' => $id,
            'paciente_id' => $pacienteId,
            'profesional_id' => $profesionalId,
            'estado_cita_id' => 8121,
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addMinutes(30),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function tratamiento(int $id, int $pacienteId, int $profesionalId): array
    {
        return [
            'id' => $id,
            'paciente_id' => $pacienteId,
            'servicio_id' => 8131,
            'profesional_id' => $profesionalId,
            'precio_acordado' => 100,
            'estado' => 'PLANIFICADO',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function tratamientoCita(int $id, int $tratamientoId, int $citaId): array
    {
        return [
            'id' => $id,
            'tratamiento_paciente_id' => $tratamientoId,
            'cita_id' => $citaId,
            'numero_sesion' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function idsInertia($respuesta, string $ruta): array
    {
        return collect(
            data_get($respuesta->viewData('page'), "props.{$ruta}", [])
        )->pluck('id')->values()->all();
    }
}
