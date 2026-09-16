<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolesPermisosTest extends TestCase
{
    use DatabaseTransactions;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        $this->administrador = User::factory()->create([
            'id' => 2,
            'name' => 'Administrador',
            'email' => 'admin@cabanillas.com',
        ]);

        $this->seed(RolesPermisosSeeder::class);
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        parent::tearDown();
    }

    public function test_el_seeder_es_idempotente_y_asigna_el_administrador_existente(): void
    {
        $this->seed(RolesPermisosSeeder::class);

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('permissions', 32);
        $this->assertDatabaseCount('model_has_roles', 1);
        $this->assertDatabaseCount(
            'role_has_permissions',
            58
        );

        $this->administrador->refresh();

        $this->assertTrue(
            $this->administrador->hasRole(
                'ADMINISTRADOR'
            )
        );

        $this->assertCount(
            32,
            $this->administrador
                ->getAllPermissions()
        );
    }

    public function test_cada_rol_tiene_exactamente_los_permisos_definidos(): void
    {
        $this->assertRolePermissions(
            'ADMINISTRADOR',
            Permission::query()
                ->pluck('name')
                ->all()
        );

        $this->assertRolePermissions(
            'RECEPCIONISTA',
            [
                'dashboard.ver',
                'pacientes.ver',
                'pacientes.crear',
                'pacientes.editar',
                'citas.ver',
                'citas.crear',
                'citas.editar',
                'citas.confirmar',
                'citas.cancelar',
                'citas.no_asistio',
                'citas.reprogramar',
                'pagos.ver',
                'pagos.registrar',
                'tratamientos.ver',
                'profesionales.ver',
                'servicios.ver',
                'consultorios.ver',
            ]
        );

        $this->assertRolePermissions(
            'ODONTOLOGO',
            [
                'dashboard.ver',
                'pacientes.ver',
                'citas.ver',
                'citas.iniciar',
                'citas.finalizar',
                'tratamientos.ver',
                'tratamientos.crear',
                'tratamientos.editar',
                'tratamientos.finalizar',
            ]
        );
    }

    public function test_un_usuario_sin_permiso_recibe_403_y_un_invitado_vuelve_al_login(): void
    {
        $this->get('/clinica')
            ->assertRedirect('/');

        $usuario = User::factory()->create([
            'id' => 10,
        ]);

        $this->actingAs($usuario)
            ->get('/clinica')
            ->assertForbidden();
    }

    public function test_las_consultas_de_pagos_solo_requieren_pagos_ver(): void
    {
        $lector = User::factory()->create([
            'id' => 11,
        ]);
        $lector->givePermissionTo('pagos.ver');

        $this->actingAs($lector);

        $this->get('/clinica/pagos')
            ->assertOk();

        $this->getJson('/clinica/pagos/pendientes')
            ->assertOk();

        $this->getJson(
            '/clinica/pagos/buscar-citas'
        )->assertUnprocessable();

        $this->postJson('/clinica/pagos')
            ->assertForbidden();
    }

    public function test_recepcion_puede_consultar_catalogos_pero_no_modificarlos(): void
    {
        $recepcionista = User::factory()->create([
            'id' => 12,
        ]);
        $recepcionista->assignRole('RECEPCIONISTA');

        $this->actingAs($recepcionista);

        $this->get('/clinica/citas')
            ->assertOk();

        $this->get('/clinica/pagos')
            ->assertOk();

        $this->postJson('/clinica/pagos')
            ->assertUnprocessable();

        $this->postJson('/clinica/estados-cita')
            ->assertForbidden();

        $this->postJson('/clinica/metodos-pago')
            ->assertForbidden();
    }

    public function test_cambiar_estado_aplica_el_permiso_dinamico_antes_de_mutar(): void
    {
        $this->crearCitaParaAutorizacion();

        $recepcionista = User::factory()->create([
            'id' => 13,
        ]);
        $recepcionista->assignRole('RECEPCIONISTA');

        $this->actingAs($recepcionista)
            ->patchJson(
                '/clinica/citas/100/estado',
                ['estado_cita_id' => 102]
            )
            ->assertForbidden();

        $odontologo = User::factory()->create([
            'id' => 14,
        ]);
        $odontologo->assignRole('ODONTOLOGO');

        $this->actingAs($odontologo)
            ->patchJson(
                '/clinica/citas/100/estado',
                ['estado_cita_id' => 101]
            )
            ->assertForbidden();

        $this->actingAs($odontologo)
            ->patchJson(
                '/clinica/citas/100/estado',
                ['estado_cita_id' => 103]
            )
            ->assertForbidden();

        $lector = User::factory()->create([
            'id' => 15,
        ]);
        $lector->givePermissionTo('citas.ver');

        $this->actingAs($lector)
            ->patchJson(
                '/clinica/citas/100/estado',
                ['estado_cita_id' => 100]
            )
            ->assertForbidden();
    }

    public function test_login_y_logout_de_fortify_siguen_funcionando(): void
    {
        $this->post('/login', [
            'email' => 'admin@cabanillas.com',
            'password' => 'password',
        ])->assertRedirect('/clinica');

        $this->assertAuthenticatedAs(
            $this->administrador
        );

        $this->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_administrador_recibe_roles_y_permisos_en_inertia(): void
    {
        $this->actingAs($this->administrador)
            ->get('/clinica')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('auth.user.id', 2)
                    ->where(
                        'auth.roles',
                        ['ADMINISTRADOR']
                    )
                    ->has('auth.permissions', 32)
            );
        foreach ([
            '/clinica/pacientes',
            '/clinica/citas',
            '/clinica/pagos',
            '/clinica/profesionales',
            '/clinica/servicios',
            '/clinica/consultorios',
        ] as $ruta) {
            $this->get($ruta)
                ->assertOk();
        }
    }

    private function crearCitaParaAutorizacion(): void
    {
        $ahora = now();

        DB::table('pacientes')->insert([
            'id' => 100,
            'codigo' => 'PAC-TEST-100',
            'tipo_documento' => 'DNI',
            'numero_documento' => 'TEST-100',
            'nombres' => 'Paciente',
            'apellidos' => 'Prueba',
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        DB::table('profesionales')->insert([
            'id' => 100,
            'nombres' => 'Odontologo',
            'apellidos' => 'Prueba',
            'numero_documento' => 'PRO-TEST-100',
            'numero_colegiatura' => 'COP-TEST-100',
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        DB::table('estados_cita')->insert([
            [
                'id' => 100,
                'codigo' => 'PENDIENTE',
                'nombre' => 'Pendiente',
                'es_final' => false,
            ],
            [
                'id' => 101,
                'codigo' => 'CONFIRMADA',
                'nombre' => 'Confirmada',
                'es_final' => false,
            ],
            [
                'id' => 102,
                'codigo' => 'EN_ATENCION',
                'nombre' => 'En atención',
                'es_final' => false,
            ],
            [
                'id' => 103,
                'codigo' => 'NO_ASISTIO',
                'nombre' => 'No asistió',
                'es_final' => true,
            ],
        ]);

        DB::table('citas')->insert([
            'id' => 100,
            'paciente_id' => 100,
            'profesional_id' => 100,
            'consultorio_id' => null,
            'estado_cita_id' => 100,
            'fecha_hora_inicio' => $ahora->copy()
                ->addDay(),
            'fecha_hora_fin' => $ahora->copy()
                ->addDay()
                ->addHour(),
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);
    }

    /**
     * @param  array<int, string>  $permisosEsperados
     */
    private function assertRolePermissions(
        string $rol,
        array $permisosEsperados
    ): void {
        $permisosActuales = Role::findByName(
            $rol,
            'web'
        )
            ->permissions
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        sort($permisosEsperados);

        $this->assertSame(
            $permisosEsperados,
            $permisosActuales
        );
    }
}
