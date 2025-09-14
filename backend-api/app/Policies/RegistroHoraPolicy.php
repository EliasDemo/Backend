<?php

namespace App\Policies;

use App\Models\RegistroHora;
use App\Models\User;

class RegistroHoraPolicy
{
    public function before(User $user, string $ability): bool|null
    {
        // Super-admin ya lo tienes con Gate::before, pero si quisieras duplicar:
        // return $user->hasRole('Administrador') ? true : null;
        return null;
    }

    public function viewAny(User $user): bool
    {
        // Cualquiera autenticado que tenga algún permiso relacionado puede listar (filtraremos por ámbito)
        return $user->can('horas.view_own') || $user->can('horas.view_program') || $user->can('horas.view_campus') || $user->hasRole('Administrador');
    }

    public function view(User $user, RegistroHora $row): bool
    {
        if ($user->hasRole('Administrador')) return true;

        // Estudiante dueño (propietario del registro)
        if ($user->hasRole('Estudiante') && $row->estudiante?->persona_id === $user->persona_id) {
            return $user->can('horas.view_own');
        }

        // Coordinador del EP–Sede del periodo actual
        if ($user->hasRole('Coordinador') && $user->can('horas.view_program')) {
            return $user->persona
                ? \App\Models\CoordinadorEpSede::query()
                    ->where('persona_id', $user->persona_id)
                    ->where('activo', true)
                    ->whereHas('periodo', fn($q)=>$q->actual())
                    ->where('ep_sede_id', $row->ep_sede_id)
                    ->exists()
                : false;
        }

        // Encargado de la Sede del periodo actual
        if ($user->hasRole('Encargado') && $user->can('horas.view_campus')) {
            $sedeId = $row->sede_id ?? $row->epSede?->sede_id;
            return $user->persona && $sedeId
                ? \App\Models\EncargadoSede::query()
                    ->where('persona_id', $user->persona_id)
                    ->where('activo', true)
                    ->whereHas('periodo', fn($q)=>$q->actual())
                    ->where('sede_id', $sedeId)
                    ->exists()
                : false;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Estudiante crea su propio registro
        if ($user->hasRole('Estudiante')) return $user->can('horas.create');
        // Admin también puede (si lo deseas)
        return $user->hasRole('Administrador');
    }

    public function update(User $user, RegistroHora $row): bool
    {
        if ($user->hasRole('Administrador')) return true;

        // Estudiante puede editar sólo si es suyo y tienes regla (p.ej. mientras esté en borrador)
        if ($user->hasRole('Estudiante') && $user->can('horas.update_own')) {
            return $row->estudiante?->persona_id === $user->persona_id
                && in_array($row->estado, ['BORRADOR','OBSERVADO'], true);
        }

        // Coordinador puede editar/observar dentro de su EP–Sede
        if ($user->hasRole('Coordinador') && $user->can('horas.review')) {
            return $this->view($user, $row); // reutiliza el mismo scoping
        }

        // Encargado puede editar/observar dentro de su Sede (si aplica a tu flujo)
        if ($user->hasRole('Encargado') && $user->can('horas.review')) {
            return $this->view($user, $row);
        }

        return false;
    }

    public function approve(User $user, RegistroHora $row): bool
    {
        if ($user->hasRole('Administrador')) return true;

        // Coordinador aprueba si está en su ámbito y cuenta con permiso
        if ($user->hasRole('Coordinador') && $user->can('horas.approve')) {
            return $this->view($user, $row);
        }

        // Encargado aprueba si lo definiste así (si no, bórralo)
        if ($user->hasRole('Encargado') && $user->can('horas.approve')) {
            return $this->view($user, $row);
        }

        return false;
    }

    public function reject(User $user, RegistroHora $row): bool
    {
        if ($user->hasRole('Administrador')) return true;

        if ($user->hasRole('Coordinador') && $user->can('horas.reject')) {
            return $this->view($user, $row);
        }
        if ($user->hasRole('Encargado') && $user->can('horas.reject')) {
            return $this->view($user, $row);
        }
        return false;
    }

    public function export(User $user): bool
    {
        if ($user->hasRole('Administrador')) return true;

        if ($user->hasRole('Coordinador')) return $user->can('horas.export');
        if ($user->hasRole('Encargado'))   return $user->can('horas.export');

        // Estudiante NO exporta (o podrías permitir report.view_own)
        return false;
    }
}
