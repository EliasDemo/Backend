<?php

namespace App\Services\Vm;

use App\Models\{VmProceso, VmProyecto, VmEvento};

class EstadoService
{
    public function recalcOwner($owner): void
    {
        if (!$owner) return;

        if ($owner instanceof VmProceso) {
            $this->recalcProcesoYProyecto($owner);
        } elseif ($owner instanceof VmEvento) {
            $this->recalcEvento($owner);
        }
    }

    public function recalcProcesoYProyecto(VmProceso $proceso): void
    {
        if ($proceso->estado === 'CANCELADO') return;

        $hasRun    = $proceso->sesiones()->where('estado','EN_CURSO')->exists();
        $hasPlan   = $proceso->sesiones()->where('estado','PLANIFICADO')->exists();
        $hasAny    = $proceso->sesiones()->exists();
        $allClosed = !$hasRun && !$hasPlan && $hasAny;

        $newProc = $hasRun ? 'EN_CURSO' : ($allClosed ? 'CERRADO' : 'PLANIFICADO');
        if ($proceso->estado !== $newProc) $proceso->update(['estado'=>$newProc]);

        $proy = $proceso->proyecto;
        if ($proy && $proy->estado !== 'CANCELADO') {
            $anyRun        = $proy->procesos()->where('estado','EN_CURSO')->exists();
            $anyPlan       = $proy->procesos()->where('estado','PLANIFICADO')->exists();
            $hasProcs      = $proy->procesos()->exists();
            $allClosedProc = !$anyRun && !$anyPlan && $hasProcs;

            $newProy = $anyRun ? 'EN_CURSO' : ($allClosedProc ? 'CERRADO' : 'PLANIFICADO');
            if ($proy->estado !== $newProy) $proy->update(['estado'=>$newProy]);
        }
    }

    public function recalcEvento(VmEvento $evento): void
    {
        if ($evento->estado === 'CANCELADO') return;

        $hasRun    = $evento->sesiones()->where('estado','EN_CURSO')->exists();
        $hasPlan   = $evento->sesiones()->where('estado','PLANIFICADO')->exists();
        $hasAny    = $evento->sesiones()->exists();
        $allClosed = !$hasRun && !$hasPlan && $hasAny;

        $newEvento = $hasRun ? 'EN_CURSO' : ($allClosed ? 'CERRADO' : 'PLANIFICADO');
        if ($evento->estado !== $newEvento) $evento->update(['estado'=>$newEvento]);
    }
}
