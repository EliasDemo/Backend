<?php

namespace App\Services\Coordinacion;

use App\Models\CoordinadorEpSede;
use App\Models\Estudiante;
use App\Models\PeriodoAcademico;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AsignarEstudianteAEpService
{
    public function asignarPorCoordinadorYEstudiante(int $personaCoordinadorId, Estudiante $estudiante): array
    {
        $periodo = PeriodoAcademico::query()->actual()->first();
        if (! $periodo) throw new RuntimeException('No existe periodo académico marcado como actual.');

        $coordinacion = CoordinadorEpSede::query()
            ->where('persona_id', $personaCoordinadorId)
            ->where('periodo_id', $periodo->id)
            ->where('activo', true)
            ->firstOrFail();

        $epSedeId = (int) $coordinacion->ep_sede_id;

        DB::transaction(function () use ($estudiante, $epSedeId) {
            $estudiante->update(['ep_sede_id' => $epSedeId]);
        });

        $estudiante->refresh();

        return compact('periodo','epSedeId','estudiante');
    }
}
