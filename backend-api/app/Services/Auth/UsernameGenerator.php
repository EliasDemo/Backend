<?php

namespace App\Services\Auth;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Str;

class UsernameGenerator
{
    public function generate(Persona $p): string
    {
        // 1) Si hay correo institucional UPeU, usar local-part
        if ($p->email_institucional && str_contains($p->email_institucional, '@')) {
            $base = Str::lower(Str::before($p->email_institucional, '@'));
            $base = Str::slug($base, '.');
            if ($base !== '') {
                return $this->ensureUnique($base);
            }
        }

        // 2) Apellido principal + inicial de nombre
        $apellidos = trim($p->apellidos);
        $nombres   = trim($p->nombres);

        $apellidoBase = Str::slug(Str::of($apellidos)->explode(' ')->first() ?? '', '.');
        $inicial      = Str::lower(Str::substr($nombres, 0, 1));
        $base = trim($apellidoBase . ($inicial ? '.' . $inicial : ''), '.');

        if ($base === '') {
            // 3) Fallback por DNI o correo personal
            if ($p->dni) {
                $base = 'u' . $p->dni;
            } elseif ($p->email_personal) {
                $base = Str::slug(Str::before($p->email_personal, '@'), '.');
            } else {
                $base = 'user' . now()->format('His');
            }
        }

        return $this->ensureUnique($base);
    }

    private function ensureUnique(string $base): string
    {
        $candidate = $base;
        $i = 2;
        while (User::where('username', $candidate)->exists()) {
            $candidate = $base . $i;
            $i++;
        }
        return $candidate;
    }
}
