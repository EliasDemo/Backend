<?php

namespace App\Services\Auth;

use App\Enums\AccountStatus;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserProvisioningService
{
    public function __construct(private readonly UsernameGenerator $usernames) {}

    /** Crea/asocia User a la Persona y asigna rol. Devuelve [User $user, ?string $passwordTemporal] */
    public function provisionForPersona(Persona $persona, string $roleName): array
    {
        return DB::transaction(function () use ($persona, $roleName) {
            // Buscar user existente por persona_id o email
            $user = User::where('persona_id', $persona->id)->first()
                ?? User::where('email', $persona->email_institucional)->first();

            $passwordTemporal = null;

            if (! $user) {
                $username = $this->usernames->generate($persona);
                $passwordTemporal = 'UPeU' . date('Y');

                $user = User::create([
                    'username'     => $username,
                    'email'        => $persona->email_institucional ?? $persona->email_personal,
                    'password'     => Hash::make($passwordTemporal),
                    'status'       => AccountStatus::ACTIVE, // o 'active'
                    'profile_photo'=> null,
                    'persona_id'   => $persona->id,
                ]);
            } else {
                // Asocia persona si faltaba
                if (! $user->persona_id) {
                    $user->update(['persona_id' => $persona->id]);
                }
            }

            // Asegura rol
            $guard = config('permission.defaults.guard', 'web');
            $role  = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            if (! $user->hasRole($roleName)) {
                $user->assignRole($role);
            }

            return [$user, $passwordTemporal];
        });
    }
}
