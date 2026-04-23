<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles usando insert directo
        DB::table('user_roles')->insert([
            ['name' => 'administrador', 'description' => 'Acceso total al sistema', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'secretaria', 'description' => 'Puede gestionar facturas y cobros', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'tecnico', 'description' => 'Puede registrar lecturas y medidores', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Crear permisos
        $permissions = [
            // Permisos de usuarios
            ['name' => 'ver_usuarios', 'description' => 'Ver lista de usuarios'],
            ['name' => 'crear_usuario', 'description' => 'Crear nuevo usuario'],
            ['name' => 'editar_usuario', 'description' => 'Editar usuario'],
            ['name' => 'eliminar_usuario', 'description' => 'Eliminar usuario'],

            // Permisos de socios
            ['name' => 'ver_socios', 'description' => 'Ver lista de socios'],
            ['name' => 'crear_socio', 'description' => 'Crear nuevo socio'],
            ['name' => 'editar_socio', 'description' => 'Editar socio'],
            ['name' => 'eliminar_socio', 'description' => 'Eliminar socio'],

            // Permisos de medidores
            ['name' => 'ver_medidores', 'description' => 'Ver lista de medidores'],
            ['name' => 'crear_medidor', 'description' => 'Crear nuevo medidor'],
            ['name' => 'editar_medidor', 'description' => 'Editar medidor'],
            ['name' => 'eliminar_medidor', 'description' => 'Eliminar medidor'],

            // Permisos de lecturas
            ['name' => 'ver_lecturas', 'description' => 'Ver lista de lecturas'],
            ['name' => 'crear_lectura', 'description' => 'Registrar nueva lectura'],
            ['name' => 'editar_lectura', 'description' => 'Editar lectura'],
            ['name' => 'eliminar_lectura', 'description' => 'Eliminar lectura'],

            // Permisos de facturas
            ['name' => 'ver_facturas', 'description' => 'Ver lista de facturas'],
            ['name' => 'crear_factura', 'description' => 'Crear nueva factura'],
            ['name' => 'editar_factura', 'description' => 'Editar factura'],
            ['name' => 'eliminar_factura', 'description' => 'Eliminar factura'],

            // Permisos de cobros
            ['name' => 'ver_cobros', 'description' => 'Ver lista de cobros'],
            ['name' => 'crear_cobro', 'description' => 'Registrar nuevo cobro'],
            ['name' => 'editar_cobro', 'description' => 'Editar cobro'],
            ['name' => 'eliminar_cobro', 'description' => 'Eliminar cobro'],

            // Permisos de reportes
            ['name' => 'ver_reportes', 'description' => 'Ver reportes'],
            ['name' => 'exportar_reportes', 'description' => 'Exportar reportes'],

            // Permisos de configuración
            ['name' => 'ver_configuracion', 'description' => 'Ver configuración'],
            ['name' => 'editar_configuracion', 'description' => 'Editar configuración'],
        ];

        foreach ($permissions as $permission) {
            DB::table('user_permissions')->insert(array_merge($permission, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Obtener IDs de roles
        $adminRoleId = DB::table('user_roles')->where('name', 'administrador')->first()->id;
        $secretariaRoleId = DB::table('user_roles')->where('name', 'secretaria')->first()->id;
        $tecnicoRoleId = DB::table('user_roles')->where('name', 'tecnico')->first()->id;

        // Obtener IDs de permisos
        $allPermissions = DB::table('user_permissions')->get();
        $secretariaPerms = $allPermissions->filter(function ($p) {
            return in_array($p->name, [
                'ver_socios', 'editar_socio',
                'ver_medidores',
                'ver_lecturas',
                'ver_facturas', 'crear_factura', 'editar_factura',
                'ver_cobros', 'crear_cobro', 'editar_cobro',
                'ver_reportes', 'exportar_reportes',
            ]);
        });
        
        $tecnicoPerms = $allPermissions->filter(function ($p) {
            return in_array($p->name, [
                'ver_socios',
                'ver_medidores', 'crear_medidor', 'editar_medidor',
                'ver_lecturas', 'crear_lectura', 'editar_lectura',
                'ver_facturas',
                'ver_reportes',
            ]);
        });

        // Asignar permisos a roles
        // Admin: todos los permisos
        foreach ($allPermissions as $perm) {
            DB::table('permission_role')->insert([
                'user_permissions_id' => $perm->id,
                'user_roles_id' => $adminRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Secretaria
        foreach ($secretariaPerms as $perm) {
            DB::table('permission_role')->insert([
                'user_permissions_id' => $perm->id,
                'user_roles_id' => $secretariaRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Tecnico
        foreach ($tecnicoPerms as $perm) {
            DB::table('permission_role')->insert([
                'user_permissions_id' => $perm->id,
                'user_roles_id' => $tecnicoRoleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
