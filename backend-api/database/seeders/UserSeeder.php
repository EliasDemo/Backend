<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Limpia caché de permisos/roles
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Asegura rol Administrador (guard por defecto)
        $guard = config('permission.defaults.guard', 'web');
        $adminRole = Role::firstOrCreate([
            'name'       => 'Administrador',
            'guard_name' => $guard,
        ]);

        // Crea o actualiza el usuario admin (sin persona)
        /** @var \App\Models\User $user */
        $user = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email'    => 'admin@example.com',
                'password' => Hash::make('password123'), // cámbialo luego
                'status'   => AccountStatus::ACTIVE,     // o 'active' si prefieres string
            ]
        );

        // Asigna rol si aún no lo tiene
        if (! $user->hasRole('Administrador')) {
            $user->assignRole($adminRole);
        }
    }
}
