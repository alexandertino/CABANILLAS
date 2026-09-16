<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermisosSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();

        DB::transaction(function (): void {
            $permisos = [
                'dashboard.ver',

                'pacientes.ver',
                'pacientes.crear',
                'pacientes.editar',

                'citas.ver',
                'citas.crear',
                'citas.editar',
                'citas.confirmar',
                'citas.iniciar',
                'citas.finalizar',
                'citas.cancelar',
                'citas.no_asistio',
                'citas.reprogramar',

                'pagos.ver',
                'pagos.registrar',
                'pagos.anular',

                'tratamientos.ver',
                'tratamientos.crear',
                'tratamientos.editar',
                'tratamientos.finalizar',

                'profesionales.ver',
                'profesionales.crear',
                'profesionales.editar',

                'servicios.ver',
                'servicios.crear',
                'servicios.editar',

                'consultorios.ver',
                'consultorios.crear',
                'consultorios.editar',

                'usuarios.ver',
                'usuarios.crear',
                'usuarios.editar',
            ];

            foreach ($permisos as $permiso) {
                Permission::findOrCreate(
                    $permiso,
                    'web'
                );
            }

            $administrador = Role::findOrCreate(
                'ADMINISTRADOR',
                'web'
            );

            $recepcionista = Role::findOrCreate(
                'RECEPCIONISTA',
                'web'
            );

            $odontologo = Role::findOrCreate(
                'ODONTOLOGO',
                'web'
            );

            $administrador->syncPermissions(
                $permisos
            );

            $recepcionista->syncPermissions([
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
            ]);

            $odontologo->syncPermissions([
                'dashboard.ver',

                'pacientes.ver',

                'citas.ver',
                'citas.iniciar',
                'citas.finalizar',

                'tratamientos.ver',
                'tratamientos.crear',
                'tratamientos.editar',
                'tratamientos.finalizar',
            ]);

            User::query()
                ->whereKey(2)
                ->where(
                    'email',
                    'admin@cabanillas.com'
                )
                ->first()
                ?->assignRole($administrador);
        });

        app(PermissionRegistrar::class)
            ->forgetCachedPermissions();
    }
}
