<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vm\ProyectoStoreRequest;
use App\Http\Resources\Vm\VmProyectoResource;
use App\Models\VmProyecto;
use App\Services\Auth\EpScopeService;
use Illuminate\Http\JsonResponse;

class ProyectoController extends Controller
{
    /** POST /api/vm/proyectos (EP_SEDE) */
    public function store(ProyectoStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (!EpScopeService::userManagesEpSede($user->id, (int)$data['ep_sede_id'])) {
            return response()->json(['ok'=>false,'message'=>'No autorizado para esta EP_SEDE.'], 403);
        }

        $codigo = $data['codigo'] ?: sprintf('PRJ-%s-EP%s-%s',
            now()->format('YmdHis'), $data['ep_sede_id'], $user->id
        );

        $proyecto = VmProyecto::create([
            'ep_sede_id'                 => $data['ep_sede_id'],
            'periodo_id'                 => $data['periodo_id'],
            'codigo'                     => $codigo,
            'titulo'                     => $data['titulo'],
            'descripcion'                => $data['descripcion'] ?? null,
            'tipo'                       => $data['tipo'],
            'modalidad'                  => $data['modalidad'],
            'estado'                     => 'PLANIFICADO',
            'horas_planificadas'         => $data['horas_planificadas'],
            'horas_minimas_participante' => $data['horas_minimas_participante'] ?? null,
        ]);

        return response()->json(['ok'=>true,'data'=>new VmProyectoResource($proyecto)], 201);
    }

    /** PUT /api/vm/proyectos/{proyecto}/publicar (requiere ≥1 proceso) */
    public function publicar(VmProyecto $proyecto): JsonResponse
    {
        $user = request()->user();

        if (!EpScopeService::userManagesEpSede($user->id, (int)$proyecto->ep_sede_id)) {
            return response()->json(['ok'=>false,'message'=>'No autorizado para esta EP_SEDE.'], 403);
        }

        if ($proyecto->procesos()->count() < 1) {
            return response()->json([
                'ok'=>false,
                'message'=>'Debe definir al menos 1 proceso antes de publicar el proyecto.',
            ], 422);
        }

        $proyecto->update(['estado' => 'EN_CURSO']);

        return response()->json(['ok'=>true, 'data'=>$proyecto->fresh()], 200);
    }
}
