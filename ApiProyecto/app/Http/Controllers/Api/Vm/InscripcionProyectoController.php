<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use App\Models\ExpedienteAcademico;
use App\Models\Matricula;
use App\Models\PeriodoAcademico;
use App\Models\VmAsistencia;
use App\Models\VmParticipacion;
use App\Models\VmProceso;
use App\Models\VmProyecto;
use App\Services\Auth\EpScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InscripcionProyectoController extends Controller
{
    /**
     * POST /api/vm/proyectos/{proyecto}/inscribirse
     */
    public function inscribirProyecto(Request $request, VmProyecto $proyecto): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok'=>false,'message'=>'No autenticado.'], 401);
        }

        // Normalizar tipo: PROYECTO => VINCULADO (compat)
        $tipo = strtoupper((string) $proyecto->tipo);
        if ($tipo === 'PROYECTO') $tipo = 'VINCULADO';

        // 1) Proyecto vigente
        if (!in_array($proyecto->estado, ['PLANIFICADO','EN_CURSO'])) {
            return $this->fail('PROJECT_NOT_ACTIVE', 'El proyecto no admite inscripciones.', 422, [
                'estado' => $proyecto->estado,
            ]);
        }

        // 2) Expediente ACTIVO del alumno en la misma EP_SEDE
        $exp = ExpedienteAcademico::where('user_id', $user->id)
            ->where('ep_sede_id', $proyecto->ep_sede_id)
            ->where('estado', 'ACTIVO')
            ->first();

        if (!$exp) {
            return $this->fail('DIFFERENT_EP_SEDE', 'No perteneces a la EP_SEDE del proyecto o tu expediente no está ACTIVO.', 422, [
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

        if ($tipo === 'LIBRE') {
            // LIBRE: ACTIVO + misma sede
            $part = VmParticipacion::create([
                'participable_type' => VmProyecto::class,
                'participable_id'   => $proyecto->id,
                'expediente_id'     => $exp->id,
                'rol'               => 'ALUMNO',
                'estado'            => 'INSCRITO',
            ]);

            // niveles del proyecto (posiblemente vacío en LIBRE)
            $niveles = $proyecto->ciclos()->pluck('nivel')->values();

            return response()->json([
                'ok'   => true,
                'code' => 'ENROLLED',
                'data' => [
                    'participacion' => $part,
                    'proyecto'      => ['id'=>$proyecto->id, 'tipo'=>'LIBRE', 'niveles'=>$niveles],
                ],
            ], 201);
        }

        // === VINCULADO ===

        // A) Matrícula vigente en período actual
        $periodoActual = PeriodoAcademico::query()->where('es_actual', true)->first();
        if (!$periodoActual) {
            return $this->fail('NO_CURRENT_PERIOD', 'No hay un período académico marcado como actual.', 422);
        }

        $matriculaActual = Matricula::where('expediente_id', $exp->id)
            ->where('periodo_id', $periodoActual->id)
            ->first();

        if (!$matriculaActual) {
            return $this->fail(
                'NOT_ENROLLED_CURRENT_PERIOD',
                'No estás matriculado en el período académico actual.',
                422,
                ['periodo_id' => (int) $periodoActual->id, 'periodo_codigo' => $periodoActual->codigo]
            );
        }

        // B) Coincidencia: el ciclo del alumno debe estar entre los niveles del proyecto
        $cicloExp  = $this->toIntOrNull($exp->ciclo);
        $cicloMat  = $this->toIntOrNull($matriculaActual->ciclo);
        $cicloEval = $cicloMat ?? $cicloExp;

        $nivelesProyecto = $proyecto->ciclos()->pluck('nivel')->map(fn($n)=>(int)$n)->values();
        if ($cicloEval === null || !$nivelesProyecto->contains((int)$cicloEval)) {
            return $this->fail(
                'LEVEL_MISMATCH',
                "Este proyecto es para estudiantes de los ciclos: ".($nivelesProyecto->isEmpty() ? '-' : $nivelesProyecto->implode(', ')).".",
                422,
                [
                    'niveles_proyecto' => $nivelesProyecto,
                    'ciclo_expediente' => $cicloExp,
                    'ciclo_matricula'  => $cicloMat,
                    'ciclo_usado'      => $cicloEval,
                ]
            );
        }

        // C) Bloqueo si existe VINCULADO pendiente (en la misma sede; sin filtrar por ciclo)
        if ($pend = $this->buscarPendienteVinculado($exp->id, $proyecto->ep_sede_id)) {
            $reqMin = $this->minutosRequeridosProyecto($pend['proyecto']);
            $acum   = $this->minutosValidadosProyecto($pend['proyecto']->id, $exp->id);
            $faltan = max(0, $reqMin - $acum);

            $cerrado = in_array($pend['proyecto']->estado, ['CERRADO','CANCELADO']);
            $msg = 'Tienes un proyecto VINCULADO pendiente del periodo '.$pend['periodo']
                .'; te faltan '.ceil($faltan/60).' h. '
                .($cerrado
                    ? 'Ese proyecto está cerrado. No puedes inscribirte a VINCULADOS hasta regularizar. Puedes tomar LIBRES.'
                    : 'Continúa ese proyecto para completarlo.');

            return $this->fail('PENDING_LINKED_PREV', $msg, 422, [
                'proyecto_id'   => (int) $pend['proyecto']->id,
                'niveles'       => $pend['proyecto']->ciclos()->pluck('nivel')->values(),
                'periodo'       => $pend['periodo'],
                'requerido_min' => $reqMin,
                'acumulado_min' => $acum,
                'faltan_min'    => $faltan,
                'cerrado'       => $cerrado,
            ]);
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
                'proyecto'      => [
                    'id'      => $proyecto->id,
                    'tipo'    => 'VINCULADO',
                    'niveles' => $proyecto->ciclos()->pluck('nivel')->values(),
                ],
            ],
        ], 201);
    }

    /** GET /api/vm/proyectos/{proyecto}/inscritos  (STAFF) */
    public function listarInscritos(Request $request, VmProyecto $proyecto): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok'=>false,'message'=>'No autenticado.'], 401);
        }
        if (!EpScopeService::userManagesEpSede($user->id, (int)$proyecto->ep_sede_id)) {
            return response()->json(['ok'=>false,'message'=>'No autorizado para esta EP_SEDE.'], 403);
        }

        $tipo = $this->normalizarTipo($proyecto);
        $estadoFiltro = strtoupper((string) $request->query('estado', 'TODOS'));
        $roles = (array) $request->query('roles', []);

        $q = VmParticipacion::query()
            ->where('participable_type', VmProyecto::class)
            ->where('participable_id', $proyecto->id)
            ->with(['expediente.user']);

        if (!empty($roles)) {
            $q->whereIn('rol', $roles);
        }

        if ($estadoFiltro === 'ACTIVOS') {
            $q->whereIn('estado', ['INSCRITO','CONFIRMADO']);
        } elseif ($estadoFiltro === 'FINALIZADOS') {
            $q->where('estado', 'FINALIZADO');
        }

        $participaciones = $q->orderBy('id')->get();

        // Sumatoria de minutos en bloque
        $expIds = $participaciones->pluck('expediente_id')->all();
        $minByExp = $this->minutosValidadosProyectoBulk($proyecto->id, $expIds);

        $reqMin = $this->minutosRequeridosProyecto($proyecto);

        $items = $participaciones->map(function ($p) use ($reqMin, $minByExp) {
            $acum = (int) ($minByExp[$p->expediente_id] ?? 0);

            $u = optional(optional($p->expediente)->user);
            $fullName = trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: null;
            $userId = $u->id ?? null;

            return [
                'participacion_id' => (int) $p->id,
                'rol'              => $p->rol,
                'estado'           => $p->estado,
                'expediente'       => [
                    'id'     => (int) $p->expediente_id,
                    'codigo' => optional($p->expediente)->codigo_estudiante,
                    'grupo'  => optional($p->expediente)->grupo,
                    'usuario'=> [
                        'id'         => $userId ? (int)$userId : null,
                        'first_name' => $u->first_name,
                        'last_name'  => $u->last_name,
                        'full_name'  => $fullName,
                        'email'      => $u->email,
                        'celular'    => $u->celular,
                    ],
                ],
                'requerido_min' => $reqMin,
                'acumulado_min' => $acum,
                'faltan_min'    => max(0, $reqMin - $acum),
                'porcentaje'    => $reqMin ? (int) round(($acum / $reqMin) * 100) : null,
                'finalizado'    => strtoupper($p->estado) === 'FINALIZADO' || $acum >= $reqMin,
            ];
        })->values();

        $resumen = [
            'total'       => $items->count(),
            'activos'     => $items->whereIn('estado', ['INSCRITO','CONFIRMADO'])->count(),
            'finalizados' => $items->filter(fn($i) => $i['finalizado'])->count(),
        ];

        return response()->json([
            'ok'   => true,
            'code' => 'ENROLLED_LIST',
            'data' => [
                'proyecto' => [
                    'id'      => (int) $proyecto->id,
                    'tipo'    => $tipo,
                    'niveles' => $proyecto->ciclos()->pluck('nivel')->values(),
                ],
                'resumen'  => $resumen,
                'inscritos'=> $items,
            ],
        ], 200);
    }

    /** GET /api/vm/proyectos/{proyecto}/candidatos  (STAFF) */
    public function listarCandidatos(Request $request, VmProyecto $proyecto): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok'=>false,'message'=>'No autenticado.'], 401);
        }
        if (!EpScopeService::userManagesEpSede($user->id, (int)$proyecto->ep_sede_id)) {
            return response()->json(['ok'=>false,'message'=>'No autorizado para esta EP_SEDE.'], 403);
        }

        $tipo = $this->normalizarTipo($proyecto);
        $soloElegibles = filter_var($request->query('solo_elegibles', 'true'), FILTER_VALIDATE_BOOLEAN);
        $limit = (int) $request->query('limit', 0);
        $queryText = trim((string) $request->query('q', ''));

        $periodoActual = PeriodoAcademico::query()->where('es_actual', true)->first();

        $expedientes = ExpedienteAcademico::query()
            ->where('ep_sede_id', $proyecto->ep_sede_id)
            ->activos()
            ->with('user')
            ->when($queryText !== '', function ($q) use ($queryText) {
                $q->where(function ($qq) use ($queryText) {
                    $qq->where('codigo_estudiante', 'like', "%{$queryText}%")
                       ->orWhereHas('user', function ($u) use ($queryText) {
                           $u->where('first_name', 'like', "%{$queryText}%")
                             ->orWhere('last_name', 'like', "%{$queryText}%")
                             ->orWhere(DB::raw("CONCAT(first_name,' ',last_name)"), 'like', "%{$queryText}%")
                             ->orWhere('email', 'like', "%{$queryText}%")
                             ->orWhere('celular', 'like', "%{$queryText}%");
                       });
                });
            })
            ->orderBy('id')
            ->cursor();

        $nivelesProyecto = $proyecto->ciclos()->pluck('nivel')->map(fn($n)=>(int)$n)->values();

        $candidatos = [];
        $descartados = [];

        $proyectoActivo = in_array($proyecto->estado, ['PLANIFICADO','EN_CURSO']);

        foreach ($expedientes as $exp) {

            $ya = VmParticipacion::where([
                'participable_type' => VmProyecto::class,
                'participable_id'   => $proyecto->id,
                'expediente_id'     => $exp->id,
            ])->exists();

            if ($ya) {
                if (!$soloElegibles) {
                    $descartados[] = [
                        'expediente_id' => (int) $exp->id,
                        'codigo'        => $exp->codigo_estudiante,
                        'razon'         => 'ALREADY_ENROLLED',
                    ];
                }
                continue;
            }

            if (!$proyectoActivo) {
                if (!$soloElegibles) {
                    $descartados[] = [
                        'expediente_id' => (int) $exp->id,
                        'codigo'        => $exp->codigo_estudiante,
                        'razon'         => 'PROJECT_NOT_ACTIVE',
                        'meta'          => ['estado' => $proyecto->estado],
                    ];
                }
                continue;
            }

            if ($tipo === 'LIBRE') {
                $u = optional($exp->user);
                $fullName = trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: null;

                $candidatos[] = [
                    'expediente_id' => (int) $exp->id,
                    'codigo'        => $exp->codigo_estudiante,
                    'usuario'       => [
                        'id'         => $u->id ? (int)$u->id : null,
                        'first_name' => $u->first_name,
                        'last_name'  => $u->last_name,
                        'full_name'  => $fullName,
                        'email'      => $u->email,
                        'celular'    => $u->celular,
                    ],
                    'motivo'        => 'ELEGIBLE_LIBRE',
                ];
            } else { // VINCULADO

                if (!$periodoActual) {
                    if (!$soloElegibles) {
                        $descartados[] = [
                            'expediente_id' => (int) $exp->id,
                            'codigo'        => $exp->codigo_estudiante,
                            'razon'         => 'NO_CURRENT_PERIOD',
                        ];
                    }
                    continue;
                }

                $matriculaActual = Matricula::where('expediente_id', $exp->id)
                    ->where('periodo_id', $periodoActual->id)
                    ->first();

                if (!$matriculaActual) {
                    if (!$soloElegibles) {
                        $descartados[] = [
                            'expediente_id' => (int) $exp->id,
                            'codigo'        => $exp->codigo_estudiante,
                            'razon'         => 'NOT_ENROLLED_CURRENT_PERIOD',
                            'meta'          => ['periodo_id' => (int)$periodoActual->id, 'periodo_codigo' => $periodoActual->codigo],
                        ];
                    }
                    continue;
                }

                $cicloExp  = $this->toIntOrNull($exp->ciclo);
                $cicloMat  = $this->toIntOrNull($matriculaActual->ciclo);
                $cicloEval = $cicloMat ?? $cicloExp;

                if ($cicloEval === null || !$nivelesProyecto->contains((int)$cicloEval)) {
                    if (!$soloElegibles) {
                        $descartados[] = [
                            'expediente_id' => (int) $exp->id,
                            'codigo'        => $exp->codigo_estudiante,
                            'razon'         => 'LEVEL_MISMATCH',
                            'meta'          => [
                                'niveles_proyecto' => $nivelesProyecto,
                                'ciclo_expediente' => $cicloExp,
                                'ciclo_matricula'  => $cicloMat,
                                'ciclo_usado'      => $cicloEval,
                            ],
                        ];
                    }
                    continue;
                }

                if ($pend = $this->buscarPendienteVinculado($exp->id, $proyecto->ep_sede_id)) {
                    if (!$soloElegibles) {
                        $reqMin = $this->minutosRequeridosProyecto($pend['proyecto']);
                        $acum   = $this->minutosValidadosProyecto($pend['proyecto']->id, $exp->id);
                        $descartados[] = [
                            'expediente_id' => (int) $exp->id,
                            'codigo'        => $exp->codigo_estudiante,
                            'razon'         => 'PENDING_LINKED_PREV',
                            'meta'          => [
                                'proyecto_id'   => (int) $pend['proyecto']->id,
                                'niveles'       => $pend['proyecto']->ciclos()->pluck('nivel')->values(),
                                'periodo'       => $pend['periodo'],
                                'requerido_min' => $reqMin,
                                'acumulado_min' => $acum,
                                'faltan_min'    => max(0, $reqMin - $acum),
                                'cerrado'       => in_array($pend['proyecto']->estado, ['CERRADO','CANCELADO']),
                            ],
                        ];
                    }
                    continue;
                }

                $u = optional($exp->user);
                $fullName = trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: null;

                $candidatos[] = [
                    'expediente_id' => (int) $exp->id,
                    'codigo'        => $exp->codigo_estudiante,
                    'usuario'       => [
                        'id'         => $u->id ? (int)$u->id : null,
                        'first_name' => $u->first_name,
                        'last_name'  => $u->last_name,
                        'full_name'  => $fullName,
                        'email'      => $u->email,
                        'celular'    => $u->celular,
                    ],
                    'motivo'        => 'ELEGIBLE_VINCULADO',
                ];
            }

            if ($limit > 0 && count($candidatos) >= $limit) {
                break;
            }
        }

        return response()->json([
            'ok'   => true,
            'code' => 'CANDIDATES_LIST',
            'data' => [
                'proyecto'           => ['id' => (int) $proyecto->id, 'tipo' => $tipo, 'niveles' => $nivelesProyecto],
                'candidatos_total'   => count($candidatos),
                'descartados_total'  => $soloElegibles ? 0 : count($descartados),
                'candidatos'         => $candidatos,
                'no_elegibles'       => $soloElegibles ? [] : $descartados,
            ],
        ], 200);
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function normalizarTipo(VmProyecto $proyecto): string
    {
        $tipo = strtoupper((string) $proyecto->tipo);
        return $tipo === 'PROYECTO' ? 'VINCULADO' : $tipo;
    }

    private function toIntOrNull($val): ?int
    {
        if ($val === null) return null;
        if (is_numeric($val)) return (int) $val;
        $digits = preg_replace('/\D+/', '', (string) $val);
        return $digits !== '' ? (int) $digits : null;
    }

    protected function minutosValidadosProyectoBulk(int $proyectoId, array $expedienteIds): array
    {
        if (empty($expedienteIds)) return [];
        return VmAsistencia::query()
            ->select('expediente_id', DB::raw('COALESCE(SUM(minutos_validados),0) as total_min'))
            ->where('estado', 'VALIDADO')
            ->whereIn('expediente_id', $expedienteIds)
            ->whereHas('sesion', function ($q) use ($proyectoId) {
                $q->where('sessionable_type', VmProceso::class)
                  ->whereHas('sessionable', fn($qq) => $qq->where('proyecto_id', $proyectoId));
            })
            ->groupBy('expediente_id')
            ->pluck('total_min', 'expediente_id')
            ->toArray();
    }

    protected function buscarPendienteVinculado(int $expedienteId, int $epSedeId): ?array
    {
        $parts = VmParticipacion::query()
            ->where('participable_type', VmProyecto::class)
            ->where('expediente_id', $expedienteId)
            ->whereHas('participable', function ($q) use ($epSedeId) {
                $q->where('ep_sede_id', $epSedeId)
                  ->whereIn('estado', ['PLANIFICADO','EN_CURSO','CERRADO','CANCELADO'])
                  ->where(function($qq){
                      $qq->where('tipo','VINCULADO')->orWhere('tipo','PROYECTO');
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
        // Versión multiciclo: busca participaciones en proyectos de ese nivel
        $rows = DB::table('vm_participaciones as vp')
            ->join('vm_proyectos as p', function ($j) {
                $j->on('p.id', '=', 'vp.participable_id')
                  ->where('vp.participable_type', '=', VmProyecto::class);
            })
            ->join('vm_proyecto_ciclos as pc', 'pc.proyecto_id', '=', 'p.id')
            ->where('vp.expediente_id', $expedienteId)
            ->where('p.ep_sede_id', $epSedeId)
            ->whereIn('p.tipo', ['VINCULADO','PROYECTO'])
            ->where('pc.nivel', $nivel)
            ->select('vp.id','p.id as pid','p.estado')
            ->get();

        foreach ($rows as $row) {
            // Si la participación está marcada FINALIZADO en la tabla (no viene en select)
            $estadoPart = DB::table('vm_participaciones')->where('id',$row->id)->value('estado');
            if (strtoupper((string)$estadoPart) === 'FINALIZADO') return true;

            $req = $this->minutosRequeridosProyecto(VmProyecto::find($row->pid));
            $acc = $this->minutosValidadosProyecto($row->pid, $expedienteId);
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
