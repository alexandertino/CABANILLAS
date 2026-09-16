<?php

namespace Tests\Feature\Usuarios;

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GestionUsuariosTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = User::factory()->create([
            'id' => 2,
            'name' => 'Administrador',
            'email' => 'admin@cabanillas.com',
            'activo' => true,
            'profesional_id' => null,
        ]);

        $this->seed(RolesPermisosSeeder::class);
    }

    protected function tearDown(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        parent::tearDown();
    }

    public function test_acceso_al_listado_respeta_auth_y_permiso(): void
    {
        $this->get('/clinica/usuarios')->assertRedirect('/');

        $sinPermiso = User::factory()->create(['id' => 100]);
        $sinPermiso->assignRole('RECEPCIONISTA');

        $this->actingAs($sinPermiso)
            ->get('/clinica/usuarios')
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get('/clinica/usuarios')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Usuarios/Index')
                    ->where('usuarios.data.0.id', 2)
                    ->where('usuarios.data.0.rol', 'ADMINISTRADOR')
            );
    }

    public function test_crea_recepcionista_y_fuerza_profesional_nulo(): void
    {
        $this->actingAs($this->admin)
            ->post('/clinica/usuarios', [
                'name' => 'Recepción Uno',
                'email' => 'recepcion@cabanillas.com',
                'rol' => 'RECEPCIONISTA',
                'profesional_id' => 999,
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ])->assertSessionHasNoErrors();

        $usuario = User::whereEmail('recepcion@cabanillas.com')
            ->firstOrFail();

        $this->assertTrue($usuario->hasRole('RECEPCIONISTA'));
        $this->assertNull($usuario->profesional_id);
        $this->assertTrue($usuario->activo);
        $this->assertTrue(
            Hash::check('Password123', $usuario->password)
        );
    }

    public function test_crea_odontologo_e_impide_profesional_duplicado(): void
    {
        $this->crearProfesional(901);

        $this->actingAs($this->admin)
            ->post('/clinica/usuarios', [
                'name' => 'Odontólogo Uno',
                'email' => 'odontologo@cabanillas.com',
                'rol' => 'ODONTOLOGO',
                'profesional_id' => 901,
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ])->assertSessionHasNoErrors();

        $odontologo = User::whereEmail('odontologo@cabanillas.com')
            ->firstOrFail();

        $this->assertSame(901, $odontologo->profesional_id);
        $this->assertTrue($odontologo->hasRole('ODONTOLOGO'));

        $this->post('/clinica/usuarios', [
            'name' => 'Odontólogo Duplicado',
            'email' => 'duplicado@cabanillas.com',
            'rol' => 'ODONTOLOGO',
            'profesional_id' => 901,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertSessionHasErrors('profesional_id');

        $this->assertDatabaseMissing('users', [
            'email' => 'duplicado@cabanillas.com',
        ]);
    }

    public function test_actualiza_usuario_cambia_rol_y_desvincula_profesional(): void
    {
        $this->crearProfesional(902);

        $usuario = User::factory()->create([
            'id' => 102,
            'name' => 'Nombre Anterior',
            'email' => 'anterior@cabanillas.com',
            'profesional_id' => 902,
        ]);
        $usuario->assignRole('ODONTOLOGO');

        $this->actingAs($this->admin)
            ->put("/clinica/usuarios/{$usuario->id}", [
                'name' => 'Nombre Actualizado',
                'email' => 'actualizado@cabanillas.com',
                'rol' => 'RECEPCIONISTA',
                'profesional_id' => 902,
                'password' => '',
                'password_confirmation' => '',
            ])->assertSessionHasNoErrors();

        $usuario->refresh();

        $this->assertSame('Nombre Actualizado', $usuario->name);
        $this->assertSame(
            'actualizado@cabanillas.com',
            $usuario->email
        );
        $this->assertNull($usuario->profesional_id);
        $this->assertTrue($usuario->hasRole('RECEPCIONISTA'));
        $this->assertCount(1, $usuario->roles);
    }

    public function test_desactiva_y_reactiva_usuario(): void
    {
        $usuario = User::factory()->create([
            'id' => 103,
            'activo' => true,
        ]);
        $usuario->assignRole('RECEPCIONISTA');

        $this->actingAs($this->admin)
            ->patch("/clinica/usuarios/{$usuario->id}/desactivar")
            ->assertSessionHasNoErrors();

        $this->assertFalse($usuario->refresh()->activo);

        $this->patch("/clinica/usuarios/{$usuario->id}/reactivar")
            ->assertSessionHasNoErrors();

        $this->assertTrue($usuario->refresh()->activo);
    }

    public function test_usuario_inactivo_no_inicia_sesion_y_sesion_existente_cierra(): void
    {
        $usuario = User::factory()->create([
            'id' => 104,
            'email' => 'inactivo@cabanillas.com',
            'password' => 'Password123',
            'activo' => false,
        ]);
        $usuario->assignRole('RECEPCIONISTA');

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'Password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->actingAs($usuario)
            ->get('/clinica')
            ->assertRedirect('/')
            ->assertSessionHas(
                'error',
                'Tu cuenta está desactivada. Contacta al administrador.'
            );

        $this->assertGuest();
    }

    public function test_protege_al_ultimo_administrador_activo(): void
    {
        $this->actingAs($this->admin)
            ->patch('/clinica/usuarios/2/desactivar')
            ->assertSessionHasErrors('usuario');

        $this->assertTrue($this->admin->refresh()->activo);

        $this->put('/clinica/usuarios/2', [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'rol' => 'RECEPCIONISTA',
            'profesional_id' => null,
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasErrors('rol');

        $this->assertTrue(
            $this->admin->refresh()->hasRole('ADMINISTRADOR')
        );
    }

    public function test_permite_cambiar_admin_si_otro_activo_permanece(): void
    {
        $otro = User::factory()->create([
            'id' => 105,
            'activo' => true,
        ]);
        $otro->assignRole('ADMINISTRADOR');

        $this->actingAs($this->admin)
            ->put('/clinica/usuarios/2', [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'rol' => 'RECEPCIONISTA',
                'profesional_id' => null,
                'password' => '',
                'password_confirmation' => '',
            ])->assertSessionHasNoErrors();

        $this->assertTrue(
            $this->admin->refresh()->hasRole('RECEPCIONISTA')
        );
    }

    public function test_password_opcional_se_conserva_y_nueva_funciona(): void
    {
        $usuario = User::factory()->create(['id' => 106]);
        $usuario->assignRole('RECEPCIONISTA');
        $hashAnterior = $usuario->password;

        $this->actingAs($this->admin)
            ->put("/clinica/usuarios/{$usuario->id}", [
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rol' => 'RECEPCIONISTA',
                'profesional_id' => null,
                'password' => '',
                'password_confirmation' => '',
            ])->assertSessionHasNoErrors();

        $this->assertSame(
            $hashAnterior,
            $usuario->refresh()->password
        );

        $this->put("/clinica/usuarios/{$usuario->id}", [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'rol' => 'RECEPCIONISTA',
            'profesional_id' => null,
            'password' => 'NuevaPassword123',
            'password_confirmation' => 'NuevaPassword123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(
            Hash::check(
                'NuevaPassword123',
                $usuario->refresh()->password
            )
        );

        $this->post('/logout');

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'NuevaPassword123',
        ])->assertRedirect('/clinica');

        $this->assertAuthenticatedAs($usuario);
    }

    private function crearProfesional(int $id): void
    {
        DB::table('profesionales')->insert([
            'id' => $id,
            'nombres' => 'Profesional',
            'apellidos' => "Prueba {$id}",
            'numero_documento' => "DOC-{$id}",
            'numero_colegiatura' => "COP-{$id}",
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
