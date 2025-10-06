<?php

namespace App\Services;

use App\Models\User;
use App\Models\Persona;
use App\Models\EncargadoSede;
use App\Models\PeriodoAcademico;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Permission\Models\Role;

class EncargadoService
{
    /**
     * Crea/actualiza un Encargado de Sede y (opcional) crea usuario + rol.
     * @return array{encargo: EncargadoSede, user: ?User, persona: Persona, plain_password: ?string}
     * @throws AuthorizationException
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // === Guard igual al de tu seeder ===
            $guard = config('permission.defaults.guard', 'web');

            // === Asegurar rol Encargado ===
            $role = Role::firstOrCreate(['name' => 'Encargado', 'guard_name' => $guard]);

            // === Periodo (por defecto: actual) ===
            $periodoId = $data['periodo_id'] ?? PeriodoAcademico::where('es_actual', true)->value('id');
            if (!$periodoId) {
                throw new \RuntimeException('No hay periodo académico actual y no se envió periodo_id.');
            }

            // === Política: crear usuario requiere users.manage ===
            $crearUsuario     = array_key_exists('crear_usuario', $data) ? (bool) $data['crear_usuario'] : true;
            $vaACrearUsuario  = empty($data['user_id']) && $crearUsuario;

            if ($vaACrearUsuario) {
                // Lanza AuthorizationException si el usuario autenticado no tiene el permiso
                Gate::authorize('users.manage');
            }

            // === Persona y Usuario ===
            $persona = null;
            $user    = null;
            $plain   = null;

            if (!empty($data['user_id'])) {
                // Reutiliza user existente
                $user    = User::findOrFail($data['user_id']);
                $persona = $user->persona ?? null;

                if (!$persona && !empty($data['persona_id'])) {
                    $persona = Persona::findOrFail($data['persona_id']);
                    $user->persona()->associate($persona);
                    $user->save();
                }
            } else {
                // Persona existente o nueva
                if (!empty($data['persona_id'])) {
                    $persona = Persona::findOrFail($data['persona_id']);
                } else {
                    $persona = Persona::create([
                        'dni'                 => $data['dni'] ?? null,
                        'apellidos'           => trim((string) ($data['apellidos'] ?? '')),
                        'nombres'             => trim((string) ($data['nombres'] ?? '')),
                        'email_institucional' => $data['email_institucional'] ?? null,
                        'email_personal'      => $data['email_personal'] ?? null,
                        'celular'             => $data['celular'] ?? null,
                    ]);
                }

                // Crear usuario sólo si está permitido y solicitado
                if ($crearUsuario) {
                    $username = $data['username'] ?? $this->buildUsername($persona, $data);
                    $plain    = $data['password'] ?? Str::password(10);

                    $user = User::create([
                        'username'   => $username,
                        'email'      => $data['email_institucional'] ?? $data['email_personal'] ?? null,
                        'password'   => bcrypt($plain),
                        'persona_id' => $persona->id,
                        'status'     => 'active',
                    ]);
                }
            }

            // === Asignar rol Encargado (si hay usuario) ===
            if ($user && !$user->hasRole($role->name)) {
                $user->assignRole($role->name);
            }

            // === Upsert en encargados_sede ===
            $encargo = EncargadoSede::updateOrCreate(
                ['sede_id' => $data['sede_id'], 'periodo_id' => $periodoId],
                [
                    'persona_id' => $persona->id,
                    'cargo'      => $data['cargo'] ?? 'ENCARGADO DE SEDE',
                    'activo'     => array_key_exists('activo', $data) ? (bool) $data['activo'] : true,
                ]
            );

            return [
                'encargo'        => $encargo->fresh(['persona', 'sede', 'periodo']),
                'user'           => $user,
                'persona'        => $persona,
                'plain_password' => $plain,
            ];
        });
    }

    private function buildUsername(Persona $p, array $data): string
    {
        $email = $data['email_institucional'] ?? $data['email_personal'] ?? null;

        if ($email && str_contains($email, '@')) {
            $candidate = \Illuminate\Support\Str::of(explode('@', $email)[0])->lower()->slug('_');
        } else {
            $candidate = \Illuminate\Support\Str::of($p->apellidos)->explode(' ')->first();
            $candidate = \Illuminate\Support\Str::of($candidate)->lower()->slug('_')
                . '_'
                . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::substr($p->nombres, 0, 1))
                . \Illuminate\Support\Str::random(3);
        }

        $base = (string) $candidate;
        $i = 0;
        while (User::where('username', $candidate)->exists()) {
            $i++;
            $candidate = $base . $i;
        }

        return (string) $candidate;
    }
}
