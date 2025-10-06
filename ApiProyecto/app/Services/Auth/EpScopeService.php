<?php

namespace App\Services\Auth;

use App\Models\ExpedienteAcademico;

class EpScopeService
{
    /** Coord/Encargado ACTIVO en esa EP_SEDE */
    public static function userManagesEpSede(int $userId, int $epSedeId): bool
    {
        return ExpedienteAcademico::query()
            ->where('user_id', $userId)
            ->where('ep_sede_id', $epSedeId)
            ->where('estado', 'ACTIVO')
            ->whereIn('rol', ['COORDINADOR','ENCARGADO'])
            ->exists();
    }

    /** Coord/Encargado ACTIVO en alguna EP_SEDE de esa SEDE */
    public static function userManagesSede(int $userId, int $sedeId): bool
    {
        return ExpedienteAcademico::query()
            ->where('user_id', $userId)
            ->where('estado', 'ACTIVO')
            ->whereIn('rol', ['COORDINADOR','ENCARGADO'])
            ->whereHas('epSede', fn($q) => $q->where('sede_id', $sedeId))
            ->exists();
    }

    /** Coord/Encargado ACTIVO en alguna EP_SEDE de esa FACULTAD */
    public static function userManagesFacultad(int $userId, int $facultadId): bool
    {
        return ExpedienteAcademico::query()
            ->where('user_id', $userId)
            ->where('estado', 'ACTIVO')
            ->whereIn('rol', ['COORDINADOR','ENCARGADO'])
            ->whereHas('epSede.escuelaProfesional', fn($q) => $q->where('facultad_id', $facultadId))
            ->exists();
    }
}
