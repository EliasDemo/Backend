<?php
// app/Console/Commands/VmTickCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VmSesion;
use App\Services\Vm\EstadoService;
use Illuminate\Support\Facades\DB;

class VmTickCommand extends Command
{
    protected $signature = 'vm:tick';
    protected $description = 'Actualiza estados de sesiones/procesos/proyectos/eventos según fecha y hora';

    public function handle(EstadoService $estadoService): int
    {
        // Usa la timezone de la app (config/app.php -> APP_TIMEZONE)
        $now = now();                        // Carbon con tz correcta
        $nowStr = $now->format('Y-m-d H:i:s'); // lo mandamos como string a SQL

        $this->info("vm:tick @ {$nowStr}");

        DB::transaction(function () use ($estadoService, $nowStr) {

            // ─────────────────────────────
            // 1) Pasar a EN_CURSO: inicio <= now < fin
            // ─────────────────────────────
            $toStart = VmSesion::query()
                ->where('estado', 'PLANIFICADO')
                ->whereRaw("TIMESTAMP(CONCAT(fecha,' ',hora_inicio)) <= ?", [$nowStr])
                ->whereRaw("TIMESTAMP(CONCAT(fecha,' ',hora_fin)) >  ?", [$nowStr])
                ->get();

            foreach ($toStart as $s) {
                $old = $s->estado;
                $s->update(['estado' => 'EN_CURSO']);
                $estadoService->recalcOwner($s->sessionable);

                logger()->info('[vm:tick] Sesión START', [
                    'sesion_id'   => $s->id,
                    'owner'       => class_basename($s->sessionable_type).':'.$s->sessionable_id,
                    'from'        => $old,
                    'to'          => 'EN_CURSO',
                    'fecha'       => (string)$s->fecha,
                    'hora_inicio' => (string)$s->hora_inicio,
                    'hora_fin'    => (string)$s->hora_fin,
                    'now'         => $nowStr,
                ]);
            }

            // ─────────────────────────────
            // 2) Cerrar: now >= fin (fecha pasada o fin alcanzado)
            //    (evita cerrar justo en el mismo segundo de inicio mal cargado)
            // ─────────────────────────────
            $toClose = VmSesion::query()
                ->whereIn('estado', ['PLANIFICADO', 'EN_CURSO'])
                ->whereRaw("TIMESTAMP(CONCAT(fecha,' ',hora_fin)) <= ?", [$nowStr])
                // si por algún dato raro hora_fin == hora_inicio, no cierres en el mismo tick de inicio:
                ->whereRaw("TIMESTAMP(CONCAT(fecha,' ',hora_inicio)) <  ?", [$nowStr])
                ->get();

            foreach ($toClose as $s) {
                $old = $s->estado;
                $s->update(['estado' => 'CERRADO']);
                $estadoService->recalcOwner($s->sessionable);

                logger()->info('[vm:tick] Sesión CLOSE', [
                    'sesion_id'   => $s->id,
                    'owner'       => class_basename($s->sessionable_type).':'.$s->sessionable_id,
                    'from'        => $old,
                    'to'          => 'CERRADO',
                    'fecha'       => (string)$s->fecha,
                    'hora_inicio' => (string)$s->hora_inicio,
                    'hora_fin'    => (string)$s->hora_fin,
                    'now'         => $nowStr,
                ]);
            }
        });

        return self::SUCCESS;
    }
}
