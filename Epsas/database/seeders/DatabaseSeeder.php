<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar seeders de roles y permisos primero
        $this->call(RolesAndPermissionsSeeder::class);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Crear usuarios para EPSAS
        $adminUser = User::create([
            'name' => 'Carlos Alberto Mamani',
            'email' => 'carlos.mamani@aguapotable.bo',
            'password' => \Illuminate\Support\Facades\Hash::make('Admin2025!'),
        ]);
        $adminUser->assignRole('administrador');

        $secretariaUser = User::create([
            'name' => 'Rosa Elena Flores',
            'email' => 'rosa.flores@aguapotable.bo',
            'password' => \Illuminate\Support\Facades\Hash::make('Secret2025!'),
        ]);
        $secretariaUser->assignRole('secretaria');

        $tecnicoUser = User::create([
            'name' => 'Pedro Luis Condori',
            'email' => 'pedro.condori@aguapotable.bo',
            'password' => \Illuminate\Support\Facades\Hash::make('Tecnic2025!'),
        ]);
        $tecnicoUser->assignRole('tecnico');
    }
}
