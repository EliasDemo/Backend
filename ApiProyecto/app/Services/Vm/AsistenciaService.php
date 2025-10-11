<?php

namespace App\Services\Vm;

use App\Models\User;
use App\Models\VmSesion;
use App\Models\VmProceso;
use App\Models\VmEvento;
use App\Models\VmProyecto;
use App\Models\VmQrToken;
use App\Models\VmAsistencia;
use App\Models\VmParticipacion;
use App\Models\RegistroHora;
use App\Models\ExpedienteAcademico;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AsistenciaService
{
    public const WINDOW_MINUTES = 30;

    public function generarToken(VmSesion $sesion, string $tipo = 'QR', ?array $geo = null, ?int $maxUsos = null, ?int $creadoPor = null): VmQrToken
    {
        $now = now();
        return DB::transaction(function () use ($sesion, $tipo, $geo, $maxUsos, $creadoPor, $now) {
            return VmQrToken::create([
                'tipo'        => $tipo, // 'QR' | 'MANUAL'
                'sesion_id'   => $sesion->id,
                'token'       => bin2hex(random_bytes(16)),
                'usable_from' => $now,
                'expires_at'  => $now->copy()->addMinutes(self::WINDOW_MINUTES),
                'max_usos'    => $maxUsos,
                'usos'        => 0,
                'activo'      => true,
                'creado_por'  => $creadoPor,
                'lat'         => $geo['lat'] ?? null,
                'lng'         => $geo['lng'] ?? null,
                'radio_m'     => $geo['radio_m'] ?? null,
                'meta'        => null,
            ]);
        });
    }

    public function checkVentana(VmQrToken $t): void
    {
        $now = now();
        if (!$t->activo || ($t->usable_from && $now->lt($t->usable_from)) || ($t->expires_at && $now->gt($t->expires_at))) {
            throw ValidationException::withMessages(['token' => 'VENTANA_INVALIDA']);
        }
        if ($t->max_usos !== null && $t->usos >= $t->max_usos) {
            throw ValidationException::withMessages(['token' => 'VENTANA_SIN_CUPO']);
        }
    }

    public function checkGeofence(?VmQrToken $t, ?float $lat, ?float $lng): void
    {
        if (!$t || !$t->lat || !$t->lng || !$t->radio_m) return;
        if ($lat === null || $lng === null) {
            throw ValidationException::withMessages(['geo' => 'GEO_REQUERIDA']);
        }
        $dist = $this->haversine((float)$t->lat, (float)$t->lng, $lat, $lng);
        if ($dist > (int)$t->radio_m) {
            throw ValidationException::withMessages(['geo' => 'FUERA_DE_RANGO']);
        }
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earth = 6371000; // m
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)**2;
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return (int) round($earth * $c);
    }

    public function resolverExpedientePorUser(User $user, ?int $epSedeId): ?ExpedienteAcademico
    {
        if (!$epSedeId) return null;
        return ExpedienteAcademico::where('user_id', $user->id)->where('ep_sede_id', $epSedeId)->first();
    }

    public function resolverExpedientePorIdentificador(string $dniOCodigo, ?int $epSedeId): ?ExpedienteAcademico
    {
        if (!$epSedeId) return null;

        return ExpedienteAcademico::query()
            ->where('ep_sede_id', $epSedeId)
            ->where(function ($q) use ($dniOCodigo) {
                $q->where('codigo_estudiante', $dniOCodigo)
                  ->orWhereHas('user', fn($qq) => $qq->where('doc_numero', $dniOCodigo));
            })
            ->first();
    }

    public function minutosSesion(VmSesion $sesion): int
    {
        if ($sesion->hora_inicio && $sesion->hora_fin) {
            $inicio = Carbon::createFromFormat('H:i:s', $sesion->hora_inicio);
            $fin    = Carbon::createFromFormat('H:i:s', $sesion->hora_fin);
            return max(0, $fin->diffInMinutes($inicio));
        }
        return (int) ($sesion->duracion_minutos ?? 0);
    }

    public function validarAsistencia(VmAsistencia $a, int $minutos, bool $crearRegistroHoras = true): VmAsistencia
    {
        $a->estado = 'VALIDADO';
        $a->minutos_validados = $minutos;
        $a->check_out_at = $a->check_out_at ?? now();
        $a->save();

        if ($crearRegistroHoras && $minutos > 0) {
            $this->crearOActualizarRegistroHora($a, $minutos);
        }

        return $a;
    }

    // ======= Helpers para crear registro_horas =======

    protected function crearOActualizarRegistroHora(VmAsistencia $a, int $minutos): void
    {
        $sesion = $a->sesion;
        [$ptype, $pid, $epSedeId, $periodoId] = $this->datosDesdeSesion($sesion);

        if (!$ptype || !$pid || !$epSedeId || !$periodoId) {
            // Si faltan datos estructurales, no generamos el registro (o lanza excepción si prefieres)
            return;
        }

        // Idempotencia: UNIQUE(asistencia_id)
        $reg = RegistroHora::firstOrNew(['asistencia_id' => $a->id]);

        $reg->fill([
            'expediente_id'  => $a->expediente_id,
            'ep_sede_id'     => $epSedeId,
            'periodo_id'     => $periodoId,
            'fecha'          => $sesion->fecha,
            'minutos'        => $minutos,
            'actividad'      => 'Asistencia sesión '.$sesion->id,
            'estado'         => 'APROBADO',
            'vinculable_type'=> $ptype,
            'vinculable_id'  => $pid,
            'sesion_id'      => $sesion->id,
        ]);

        // Si ya existía y cambió la duración, actualiza minutos/estado
        if ($reg->exists) {
            $reg->minutos = $minutos;
            $reg->estado  = 'APROBADO';
        }

        $reg->save();
    }

    /** @return array{0:?string,1:?int,2:?int,3:?int} [ptype, pid, ep_sede_id, periodo_id] */
    protected function datosDesdeSesion(VmSesion $sesion): array
    {
        // VmProceso -> VmProyecto
        if ($sesion->sessionable_type === VmProceso::class) {
            $proc = $sesion->sessionable;
            if ($proc) {
                // Si tienes relación $proc->proyecto:
                if (method_exists($proc, 'proyecto') && $proc->proyecto) {
                    $p = $proc->proyecto;
                    return [VmProyecto::class, (int)$p->id, (int)$p->ep_sede_id, (int)$p->periodo_id];
                }
                // Fallback por FK
                if (isset($proc->proyecto_id)) {
                    $p = VmProyecto::select('id','ep_sede_id','periodo_id')->find($proc->proyecto_id);
                    if ($p) return [VmProyecto::class, (int)$p->id, (int)$p->ep_sede_id, (int)$p->periodo_id];
                }
            }
        }

        // VmEvento directo
        if ($sesion->sessionable_type === VmEvento::class) {
            $evt = $sesion->sessionable;
            if ($evt && isset($evt->id, $evt->ep_sede_id, $evt->periodo_id)) {
                return [VmEvento::class, (int)$evt->id, (int)$evt->ep_sede_id, (int)$evt->periodo_id];
            }
        }

        return [null, null, null, null];
    }

    public function epSedeIdDesdeSesion(VmSesion $sesion): ?int
    {
        [$ptype, $pid, $ep, $per] = $this->datosDesdeSesion($sesion);
        return $ep;
    }

    /** @return array{0:?string,1:?int} [participable_type, participable_id] */
    private function participableDesdeSesion(VmSesion $sesion): array
    {
        if ($sesion->sessionable_type === VmProceso::class) {
            $proc = $sesion->sessionable;
            $pid = $proc->proyecto_id ?? null;
            return $pid ? [VmProyecto::class, (int)$pid] : [null, null];
        }
        if ($sesion->sessionable_type === VmEvento::class) {
            return [VmEvento::class, (int)$sesion->sessionable_id];
        }
        return [null, null];
    }

    public function upsertAsistencia(VmSesion $sesion, ExpedienteAcademico $exp, string $metodo, ?VmQrToken $token = null, ?array $meta = null): VmAsistencia
    {
        return DB::transaction(function () use ($sesion, $exp, $metodo, $token, $meta) {
            /** @var VmAsistencia $a */
            $a = VmAsistencia::firstOrNew([
                'sesion_id'     => $sesion->id,
                'expediente_id' => $exp->id,
            ]);

            if (!$a->exists || !$a->check_in_at) {
                $a->check_in_at = now();
            }

            // Intentar asociar participación (si existe)
            [$ptype, $pid] = $this->participableDesdeSesion($sesion);
            if ($ptype && $pid) {
                $a->participacion_id = VmParticipacion::where([
                    'participable_type' => $ptype,
                    'participable_id'   => $pid,
                    'expediente_id'     => $exp->id,
                ])->value('id');
            }

            $a->metodo            = $metodo; // 'QR' o 'MANUAL'
            $a->qr_token_id       = $token?->id;
            $a->estado            = $a->estado ?: 'PENDIENTE';
            $a->minutos_validados = $a->minutos_validados ?? 0;

            // merge meta
            $prev = is_array($a->meta) ? $a->meta : [];
            $a->meta = array_merge($prev, $meta ?? []);
            $a->save();

            if ($token) {
                $token->increment('usos');
            }

            return $a->refresh();
        });
    }
}
