<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use App\Models\ExpedienteAcademico;
use App\Models\VmAsistencia;
use App\Models\VmParticipacion;
use App\Models\VmProceso;
use App\Models\VmProyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InscripcionProyectoController extends Controller
{
    /**
     * POST /api/vm/proyectos/{proyecto}/inscribirse
     *
     * Respuestas (status, code):
     * - 201 ENROLLED
     * - 422 PROJECT_NOT_ACTIVE | DIFFERENT_EP_SEDE | ALREADY_ENROLLED |
     *      PENDING_LINKED_PREV | LEVEL_NOT_ALLOWED | LEVEL_ALREADY_COMPLETED
     */
    public function inscribirProyecto(Request $request, VmProyecto $proyecto): JsonResponse
    {
        $user = $request->user();

        // Normalizar tipo: PROYECTO => VINCULADO (compatibilidad)
        $tipo = strtoupper((string) $proyecto->tipo);
        if ($tipo === 'PROYECTO') $tipo = 'VINCULADO';

        // 1) Proyecto vigente
        if (!in_array($proyecto->estado, ['PLANIFICADO','EN_CURSO'])) {
            return $this->fail('PROJECT_NOT_ACTIVE', 'El proyecto no admite inscripciones.', 422, [
                'estado' => $proyecto->estado,
            ]);
        }

        // 2) Expediente del alumno en la misma EP_SEDE
        $exp = ExpedienteAcademico::where('user_id', $user->id)
            ->where('ep_sede_id', $proyecto->ep_sede_id)
            ->first();

        if (!$exp) {
            return $this->fail('DIFFERENT_EP_SEDE', 'No perteneces a la EP_SEDE del proyecto.', 422, [
                'proyecto_ep_sede_id' => (int) $proyecto->ep_sede_id,
            ]);
        }

        // 3) Ya inscrito en este proyecto
        $yaInscrito = VmParticipacion::where([
            'participable_type' => VmProyecto::class,
            'participable_id'   => $proyecto->id,
            'expediente_id'     => $exp->id,
        ])->exists();

        if ($yaInscrito) {
            return $this->fail('ALREADY_ENROLLED', 'Ya estás inscrito en este proyecto.', 422);
        }

        // 4) Ramas por tipo
        if ($tipo === 'LIBRE') {
            $part = VmParticipacion::create([
                'participable_type' => VmProyecto::class,
                'participable_id'   => $proyecto->id,
                'expediente_id'     => $exp->id,
                'rol'               => 'ALUMNO',
                'estado'            => 'INSCRITO',
            ]);

            return response()->json([
                'ok'   => true,
                'code' => 'ENROLLED',
                'data' => [
                    'participacion' => $part,
                    'proyecto'      => ['id'=>$proyecto->id, 'tipo'=>'LIBRE', 'nivel'=>$proyecto->nivel],
                ],
            ], 201);
        }

        // === VINCULADO ===

        // A) Bloqueo si existe VINCULADO pendiente en cualquier nivel (horas < requeridas)
        if ($pend = $this->buscarPendienteVinculado($exp->id, $proyecto->ep_sede_id)) {
            $reqMin = $this->minutosRequeridosProyecto($pend['proyecto']);
            $acum   = $this->minutosValidadosProyecto($pend['proyecto']->id, $exp->id);
            $faltan = max(0, $reqMin - $acum);

            $cerrado = in_array($pend['proyecto']->estado, ['CERRADO','CANCELADO']);
            $msg = 'Tienes un proyecto VINCULADO pendiente (nivel '.$pend['proyecto']->nivel
                .') del periodo '.$pend['periodo'].'; te faltan '.ceil($faltan/60)
                .' h. '.($cerrado
                    ? 'Ese proyecto está cerrado. No puedes inscribirte a VINCULADOS hasta regularizar. Puedes tomar LIBRES.'
                    : 'Continúa ese proyecto para completarlo.');

            return $this->fail('PENDING_LINKED_PREV', $msg, 422, [
                'proyecto_id'   => (int) $pend['proyecto']->id,
                'nivel'         => (int) $pend['proyecto']->nivel,
                'periodo'       => $pend['periodo'],
                'requerido_min' => $reqMin,
                'acumulado_min' => $acum,
                'faltan_min'    => $faltan,
                'cerrado'       => $cerrado,
            ]);
        }

        // B) Escalera: para nivel N, el nivel N-1 debe estar FINALIZADO
        if ((int)$proyecto->nivel > 1) {
            $prev = (int)$proyecto->nivel - 1;
            if (!$this->existeNivelFinalizado($exp->id, $proyecto->ep_sede_id, $prev)) {
                return $this->fail('LEVEL_NOT_ALLOWED',
                    "Aún no corresponde este nivel. Debes completar el nivel {$prev} (VINCULADO) antes de inscribirte.",
                    422,
                    ['nivel_requerido' => $prev]
                );
            }
        }

        // C) No repetir un nivel VINCULADO ya completado en cualquier periodo
        if ($this->existeNivelFinalizado($exp->id, $proyecto->ep_sede_id, (int)$proyecto->nivel)) {
            return $this->fail('LEVEL_ALREADY_COMPLETED',
                "Ya completaste el nivel {$proyecto->nivel} (VINCULADO).",
                422,
                ['nivel' => (int)$proyecto->nivel]
            );
        }

        // D) Crear participación
        $part = DB::transaction(function () use ($proyecto, $exp) {
            return VmParticipacion::firstOrCreate(
                [
                    'participable_type' => VmProyecto::class,
                    'participable_id'   => $proyecto->id,
                    'expediente_id'     => $exp->id,
                ],
                [
                    'rol'    => 'ALUMNO',
                    'estado' => 'INSCRITO',
                ]
            );
        });

        return response()->json([
            'ok'   => true,
            'code' => 'ENROLLED',
            'data' => [
                'participacion' => $part,
                'proyecto'      => ['id'=>$proyecto->id, 'tipo'=>'VINCULADO', 'nivel'=>$proyecto->nivel],
            ],
        ], 201);
    }

    // ───────────────────────── Helpers ─────────────────────────

    protected function buscarPendienteVinculado(int $expedienteId, int $epSedeId): ?array
    {
        $parts = VmParticipacion::query()
            ->where('participable_type', VmProyecto::class)
            ->where('expediente_id', $expedienteId)
            ->whereHas('participable', function ($q) use ($epSedeId) {
                $q->where('ep_sede_id', $epSedeId)
                  ->whereIn('estado', ['PLANIFICADO','EN_CURSO','CERRADO','CANCELADO'])
                  ->where(function($qq){
                      $qq->where('tipo','VINCULADO')->orWhere('tipo','PROYECTO'); // compat
                  });
            })
            ->get();

        foreach ($parts as $p) {
            /** @var VmProyecto $proj */
            $proj = $p->participable;
            if (strtoupper($p->estado) === 'FINALIZADO') continue;

            $req = $this->minutosRequeridosProyecto($proj);
            $acc = $this->minutosValidadosProyecto($proj->id, $expedienteId);

            if ($acc < $req) {
                return [
                    'proyecto' => $proj,
                    'periodo'  => optional($proj->periodo)->codigo ?? $proj->periodo_id,
                ];
            }
        }
        return null;
    }

    protected function existeNivelFinalizado(int $expedienteId, int $epSedeId, int $nivel): bool
    {
        $parts = VmParticipacion::query()
            ->where('participable_type', VmProyecto::class)
            ->where('expediente_id', $expedienteId)
            ->whereHas('participable', function ($q) use ($epSedeId, $nivel) {
                $q->where('ep_sede_id', $epSedeId)
                  ->where('nivel', $nivel)
                  ->where(function($qq){
                      $qq->where('tipo','VINCULADO')->orWhere('tipo','PROYECTO');
                  });
            })
            ->get();

        foreach ($parts as $p) {
            if (strtoupper($p->estado) === 'FINALIZADO') return true;

            /** @var VmProyecto $proj */
            $proj = $p->participable;
            $req  = $this->minutosRequeridosProyecto($proj);
            $acc  = $this->minutosValidadosProyecto($proj->id, $expedienteId);
            if ($acc >= $req) return true;
        }
        return false;
    }

    protected function minutosRequeridosProyecto(VmProyecto $proyecto): int
    {
        $h = $proyecto->horas_minimas_participante ?: $proyecto->horas_planificadas;
        return ((int)$h) * 60;
    }

    protected function minutosValidadosProyecto(int $proyectoId, int $expedienteId): int
    {
        return (int) VmAsistencia::query()
            ->where('estado', 'VALIDADO')
            ->where('expediente_id', $expedienteId)
            ->whereHas('sesion', function ($q) use ($proyectoId) {
                $q->where('sessionable_type', VmProceso::class)
                  ->whereHas('sessionable', fn($qq) => $qq->where('proyecto_id', $proyectoId));
            })
            ->sum('minutos_validados');
    }

    // Respuesta de error consistente
    private function fail(string $code, string $message, int $status = 422, array $meta = []): JsonResponse
    {
        return response()->json([
            'ok'      => false,
            'code'    => $code,
            'message' => $message,
            'meta'    => (object) $meta,
        ], $status);
    }
}
