<?php

namespace App\Services\Horas;

use App\Models\RegistroHora;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class HorasAccessService
{
    public function scopeVisible(Builder $q, User $user): Builder
    {
        // Admin ve todo
        if ($user->hasRole('Administrador')) {
            return $q;
        }

        // Estudiante: sólo propios
        if ($user->hasRole('Estudiante') && $user->can('horas.view_own')) {
            return $q->whereHas('estudiante', function (Builder $qq) use ($user) {
                $qq->where('persona_id', $user->persona_id);
            });
        }

        // Coordinador: EP–Sede del periodo actual
        if ($user->hasRole('Coordinador') && $user->can('horas.view_program')) {
            return $q->whereIn('ep_sede_id', function ($sq) use ($user) {
                $sq->select('ep_sede_id')
                    ->from('coordinadores_ep_sede')
                    ->where('persona_id', $user->persona_id)
                    ->where('activo', true)
                    ->whereExists(function ($sqq) {
                        $sqq->selectRaw(1)
                            ->from('periodos_academicos as p')
                            ->whereColumn('p.id', 'coordinadores_ep_sede.periodo_id')
                            ->where('p.es_actual', true);
                    });
            });
        }

        // Encargado: Sede del periodo actual
        if ($user->hasRole('Encargado') && $user->can('horas.view_campus')) {
            // Si RegistroHora NO tiene sede_id, derivamos con join ep_sede
            return $q->where(function (Builder $qb) use ($user) {
                $qb->whereIn('sede_id', function ($sq) use ($user) {
                    $sq->select('sede_id')
                       ->from('encargados_sede')
                       ->where('persona_id', $user->persona_id)
                       ->where('activo', true)
                       ->whereExists(function ($sqq) {
                           $sqq->selectRaw(1)
                               ->from('periodos_academicos as p')
                               ->whereColumn('p.id', 'encargados_sede.periodo_id')
                               ->where('p.es_actual', true);
                       });
                })
                ->orWhereIn('ep_sede_id', function ($sq) use ($user) { // por si no hay sede_id en la tabla
                    $sq->select('es.id')
                       ->from('ep_sede as es')
                       ->whereIn('es.sede_id', function ($sq2) use ($user) {
                           $sq2->select('sede_id')
                               ->from('encargados_sede')
                               ->where('persona_id', $user->persona_id)
                               ->where('activo', true)
                               ->whereExists(function ($sqq) {
                                   $sqq->selectRaw(1)
                                       ->from('periodos_academicos as p')
                                       ->whereColumn('p.id', 'encargados_sede.periodo_id')
                                       ->where('p.es_actual', true);
                               });
                       });
                });
            });
        }

        // Por defecto, nada
        return $q->whereRaw('1=0');
    }
}
