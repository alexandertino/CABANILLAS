<?php

namespace Tests\Feature\Authorization;

use App\Models\Cita;
use App\Models\Pago;
use App\Models\Paciente;
use App\Models\TratamientoPaciente;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use DatabaseTransactions;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesPermisosSeeder::class);
        $this->crearCatalogos();

        $this->administrador = User::factory()->create([
            'email' => 'admin-auditoria@cabanillas.test',
            'activo' => true,
        ]);
        $this->administrador->assignRole('ADMINISTRADOR');
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        parent::tearDown();
    }

    public function test_solo_administrador_puede_consultar_la_auditoria(): void
    {
        $recepcionista = User::factory()->create();
        $recepcionista->assignRole('RECEPCIONISTA');

        $odontologo = User::factory()->create([
            'profesional_id' => 9101,
        ]);
        $odontologo->assignRole('ODONTOLOGO');

        $this->actingAs($this->administrador)
            ->get('/clinica/auditoria')
            ->assertOk();

        $this->actingAs($recepcionista)
            ->get('/clinica/auditoria')
            ->assertForbidden();

        $this->actingAs($odontologo)
            ->get('/clinica/auditoria')
            ->assertForbidden();
    }

    public function test_crear_y_editar_paciente_registra_causante_cambios_ip_y_navegador(): void
    {
        $this->actingAs($this->administrador)
            ->withHeader('User-Agent', 'AuditoriaBrowser/1.0')
            ->post('/clinica/pacientes', [
                'tipo_documento' => 'DNI',
                'numero_documento' => 'AUD-PAC-001',
                'nombres' => 'Paciente',
                'apellidos' => 'Auditable',
                'telefono' => '999111222',
                'activo' => true,
            ])
            ->assertRedirect();

        $paciente = Paciente::query()
            ->where('numero_documento', 'AUD-PAC-001')
            ->firstOrFail();

        $creacion = $this->actividad('pacientes', 'crear', $paciente->id);

        $this->assertSame($this->administrador->id, $creacion->causer_id);
        $this->assertSame('Paciente', $creacion->attribute_changes['new']['nombres']);
        $this->assertSame('AuditoriaBrowser/1.0', $creacion->properties['user_agent']);
        $this->assertNotEmpty($creacion->properties['ip']);

        $this->actingAs($this->administrador)
            ->put("/clinica/pacientes/{$paciente->id}", [
                'tipo_documento' => 'DNI',
                'numero_documento' => 'AUD-PAC-001',
                'nombres' => 'Paciente Editado',
                'apellidos' => 'Auditable',
                'telefono' => '999111222',
                'activo' => true,
            ])
            ->assertRedirect();

        $edicion = $this->actividad('pacientes', 'editar', $paciente->id);

        $this->assertSame('Paciente', $edicion->attribute_changes['old']['nombres']);
        $this->assertSame('Paciente Editado', $edicion->attribute_changes['new']['nombres']);

        $this->actingAs($this->administrador)
            ->get('/clinica/auditoria?' . http_build_query([
                'usuario' => $this->administrador->id,
                'modulo' => 'pacientes',
                'accion' => 'editar',
                'buscar' => (string) $paciente->id,
            ]))
            ->assertOk();
    }

    public function test_usuario_registra_creacion_edicion_y_desactivacion_sin_passwords(): void
    {
        $claveInicial = 'ClaveInicial-123';
        $claveNueva = 'ClaveNueva-456';

        $this->actingAs($this->administrador)
            ->post('/clinica/usuarios', [
                'name' => 'Usuario Auditado',
                'email' => 'usuario-auditado@cabanillas.test',
                'password' => $claveInicial,
                'password_confirmation' => $claveInicial,
                'rol' => 'RECEPCIONISTA',
            ])
            ->assertRedirect();

        $usuario = User::query()
            ->where('email', 'usuario-auditado@cabanillas.test')
            ->firstOrFail();

        $this->actividad('usuarios', 'crear', $usuario->id);

        $this->actingAs($this->administrador)
            ->put("/clinica/usuarios/{$usuario->id}", [
                'name' => 'Usuario Auditado Editado',
                'email' => 'usuario-auditado@cabanillas.test',
                'password' => $claveNueva,
                'password_confirmation' => $claveNueva,
                'rol' => 'RECEPCIONISTA',
            ])
            ->assertRedirect();

        $edicion = $this->actividad('usuarios', 'editar', $usuario->id);
        $this->assertTrue($edicion->properties['credencial_actualizada']);

        $this->actingAs($this->administrador)
            ->patch("/clinica/usuarios/{$usuario->id}/desactivar")
            ->assertRedirect();

        $this->actividad('usuarios', 'desactivar', $usuario->id);

        $contenido = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $usuario->id)
            ->get(['attribute_changes', 'properties'])
            ->toJson();

        $this->assertStringNotContainsString('password', strtolower($contenido));
        $this->assertStringNotContainsString(strtolower($claveInicial), strtolower($contenido));
        $this->assertStringNotContainsString(strtolower($claveNueva), strtolower($contenido));
    }

    public function test_crear_reprogramar_y_cancelar_cita_genera_actividades(): void
    {
        $this->actingAs($this->administrador)
            ->post('/clinica/citas', $this->datosCita(now()->addDays(2)))
            ->assertRedirect();

        $cita = Cita::query()->latest('id')->firstOrFail();
        $this->actividad('citas', 'crear', $cita->id);

        $tratamiento = TratamientoPaciente::query()
            ->where('paciente_id', 9111)
            ->latest('id')
            ->firstOrFail();
        $this->actividad('tratamientos', 'crear', $tratamiento->id);

        $this->actingAs($this->administrador)
            ->post("/clinica/citas/{$cita->id}/reprogramar", [
                'profesional_id' => 9101,
                'consultorio_id' => null,
                'fecha_hora_inicio' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'motivo' => 'Solicitud del paciente',
            ])
            ->assertRedirect();

        $reprogramacion = $this->actividad('citas', 'reprogramar', $cita->id);
        $nuevaCitaId = $reprogramacion->properties['nueva_cita_id'];

        $this->actingAs($this->administrador)
            ->patch("/clinica/citas/{$nuevaCitaId}/cancelar", [
                'motivo_cancelacion' => 'Prueba de auditoría',
            ])
            ->assertRedirect();

        $this->actividad('citas', 'cancelar', (int) $nuevaCitaId);
    }

    public function test_iniciar_y_finalizar_atencion_audita_cita_y_tratamiento(): void
    {
        $this->actingAs($this->administrador)
            ->post('/clinica/citas', $this->datosCita(now()->addDays(5)))
            ->assertRedirect();

        $cita = Cita::query()->latest('id')->firstOrFail();
        $tratamiento = TratamientoPaciente::query()->latest('id')->firstOrFail();

        $this->actingAs($this->administrador)
            ->patch("/clinica/citas/{$cita->id}/estado", [
                'estado_cita_id' => 9123,
            ])
            ->assertRedirect();

        $this->actividad('citas', 'iniciar', $cita->id);
        $this->actividad('tratamientos', 'iniciar', $tratamiento->id);

        $this->actingAs($this->administrador)
            ->patch("/clinica/citas/{$cita->id}/finalizar-atencion", [
                'accion_tratamiento' => 'COMPLETAR',
            ])
            ->assertRedirect();

        $this->actividad('citas', 'finalizar', $cita->id);
        $this->actividad('tratamientos', 'completar', $tratamiento->id);
    }

    public function test_registrar_y_anular_pago_genera_actividad_con_cambios(): void
    {
        $tratamiento = TratamientoPaciente::create([
            'paciente_id' => 9111,
            'servicio_id' => 9131,
            'profesional_id' => 9101,
            'precio_acordado' => 200,
            'estado' => TratamientoPaciente::ESTADO_PLANIFICADO,
        ]);

        $this->actingAs($this->administrador)
            ->post('/clinica/pagos', [
                'tipo_origen' => 'TRATAMIENTO',
                'tratamiento_paciente_id' => $tratamiento->id,
                'metodo_pago_id' => 9141,
                'monto' => 50,
                'fecha_pago' => now()->format('Y-m-d H:i:s'),
                'numero_operacion' => 'AUD-OP-001',
            ])
            ->assertRedirect();

        $pago = Pago::query()->latest('id')->firstOrFail();
        $registro = $this->actividad('pagos', 'registrar', $pago->id);
        $this->assertSame('50.00', $registro->attribute_changes['new']['monto']);

        $this->actingAs($this->administrador)
            ->patch("/clinica/pagos/{$pago->id}/anular", [
                'motivo_anulacion' => 'Operación de prueba',
            ])
            ->assertRedirect();

        $anulacion = $this->actividad('pagos', 'anular', $pago->id);
        $this->assertSame('REGISTRADO', $anulacion->attribute_changes['old']['estado']);
        $this->assertSame('ANULADO', $anulacion->attribute_changes['new']['estado']);
    }

    public function test_login_logout_y_bloqueo_de_inactivo_generan_actividad(): void
    {
        $usuario = User::factory()->create([
            'email' => 'sesion-auditoria@cabanillas.test',
            'password' => 'password',
            'activo' => true,
        ]);

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'password',
        ])->assertRedirect('/clinica');

        $this->actividad('autenticacion', 'login', $usuario->id);

        $this->post('/logout')->assertRedirect('/');
        $this->actividad('autenticacion', 'logout', $usuario->id);

        $inactivo = User::factory()->create([
            'email' => 'inactivo-auditoria@cabanillas.test',
            'password' => 'password',
            'activo' => false,
        ]);

        $this->post('/login', [
            'email' => $inactivo->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $bloqueo = $this->actividad(
            'autenticacion',
            'login_bloqueado',
            $inactivo->id
        );
        $this->assertNull($bloqueo->causer_id);
    }

    private function actividad(string $modulo, string $accion, int $sujetoId): Activity
    {
        $actividad = Activity::query()
            ->where('log_name', $modulo)
            ->where('event', $accion)
            ->where('subject_id', $sujetoId)
            ->latest('id')
            ->first();

        $this->assertNotNull(
            $actividad,
            "No se registró {$modulo}.{$accion} para #{$sujetoId}."
        );

        return $actividad;
    }

    private function datosCita($fecha): array
    {
        return [
            'paciente_id' => 9111,
            'profesional_id' => 9101,
            'consultorio_id' => null,
            'servicio_id' => 9131,
            'estado_cita_id' => 9122,
            'fecha_hora_inicio' => $fecha->format('Y-m-d H:i:s'),
            'motivo' => 'Auditoría',
            'tipo_atencion' => 'NUEVO_TRATAMIENTO',
            'precio_acordado' => 200,
        ];
    }

    private function crearCatalogos(): void
    {
        $ahora = now();

        DB::table('profesionales')->insert([
            'id' => 9101,
            'nombres' => 'Profesional',
            'apellidos' => 'Auditoría',
            'numero_documento' => 'AUD-PRO-001',
            'numero_colegiatura' => 'AUD-COP-001',
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        DB::table('pacientes')->insert([
            'id' => 9111,
            'codigo' => 'PAC-AUD-001',
            'tipo_documento' => 'DNI',
            'numero_documento' => 'AUD-BASE-001',
            'nombres' => 'Paciente Base',
            'apellidos' => 'Auditoría',
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        DB::table('estados_cita')->insert([
            ['id' => 9121, 'codigo' => 'PENDIENTE', 'nombre' => 'Pendiente', 'es_final' => false, 'activo' => true],
            ['id' => 9122, 'codigo' => 'CONFIRMADA', 'nombre' => 'Confirmada', 'es_final' => false, 'activo' => true],
            ['id' => 9123, 'codigo' => 'EN_ATENCION', 'nombre' => 'En atención', 'es_final' => false, 'activo' => true],
            ['id' => 9124, 'codigo' => 'ATENDIDA', 'nombre' => 'Atendida', 'es_final' => true, 'activo' => true],
            ['id' => 9125, 'codigo' => 'CANCELADA', 'nombre' => 'Cancelada', 'es_final' => true, 'activo' => true],
            ['id' => 9126, 'codigo' => 'REPROGRAMADA', 'nombre' => 'Reprogramada', 'es_final' => true, 'activo' => true],
            ['id' => 9127, 'codigo' => 'NO_ASISTIO', 'nombre' => 'No asistió', 'es_final' => true, 'activo' => true],
        ]);

        DB::table('servicios')->insert([
            'id' => 9131,
            'codigo' => 'SERV-AUD-001',
            'nombre' => 'Servicio auditable',
            'duracion_estimada_minutos' => 30,
            'precio_actual' => 200,
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        DB::table('metodos_pago')->insert([
            'id' => 9141,
            'codigo' => 'MET-AUD-001',
            'nombre' => 'Método auditable',
            'activo' => true,
        ]);
    }
}
