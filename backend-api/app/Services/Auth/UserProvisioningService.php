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
            // Buscar user existente por persona o por email (institucional o personal)
            $user = User::where('persona_id', $persona->id)->first()
                ?? User::where('email', $persona->email_institucional)->first()
                ?? User::where('email', $persona->email_personal)->first();

            $passwordTemporal = null;

            if (!$user) {
                $username = $this->usernames->generate($persona);
                // Puedes cambiar la política de password temporal si quieres
                $passwordTemporal = 'UPeU' . date('Y');

                // IMPORTANTE: si no agregas 'persona_id' a $fillable en User, usa associate() abajo
                $user = User::create([
                    'username'      => $username,
                    'email'         => $persona->email_institucional ?? $persona->email_personal,
                    'password'      => Hash::make($passwordTemporal),
                    'status'        => AccountStatus::ACTIVE, // o 'active'
                    'profile_photo' => null,
                    'persona_id'    => $persona->id,
                ]);

                // Alternativa sin mass-assign:
                // $user = User::create([
                //     'username' => $username,
                //     'email'    => $persona->email_institucional ?? $persona->email_personal,
                //     'password' => Hash::make($passwordTemporal),
                //     'status'   => AccountStatus::ACTIVE,
                // ]);
                // $user->persona()->associate($persona);
                // $user->save();

            } else {
                // Vincula persona si faltaba y completa email si está vacío
                $update = [];
                if (!$user->persona_id) {
                    $update['persona_id'] = $persona->id;
                }
                if (!$user->email && ($persona->email_institucional || $persona->email_personal)) {
                    $update['email'] = $persona->email_institucional ?? $persona->email_personal;
                }
                if ($update) {
                    $user->update($update);
                }
            }

            // Asegurar rol
            $guard = config('permission.defaults.guard', 'web');
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }

            $user->loadMissing(['persona', 'roles', 'permissions']);

            return [$user, $passwordTemporal];
        });
    }
}
