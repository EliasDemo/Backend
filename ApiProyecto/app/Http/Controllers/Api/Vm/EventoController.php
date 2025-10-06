<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vm\EventoStoreRequest;
use App\Http\Resources\Vm\VmEventoResource;
use App\Models\PeriodoAcademico;
use App\Models\VmEvento;
use App\Services\Auth\EpScopeService;
use Illuminate\Http\JsonResponse;

class EventoController extends Controller
{
    /** POST /api/vm/eventos  (Sede | Facultad | EP_SEDE) */
    public function store(EventoStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // Alcance según target_type
        $type = strtolower($data['target_type']);
        $targetId = (int) $data['target_id'];

        $scopeOk = match ($type) {
            'ep_sede'  => EpScopeService::userManagesEpSede($user->id, $targetId),
            'sede'     => EpScopeService::userManagesSede($user->id,  $targetId),
            'facultad' => EpScopeService::userManagesFacultad($user->id, $targetId),
            default    => false,
        };
        if (!$scopeOk) return response()->json(['ok'=>false,'message'=>'No autorizado para el target solicitado.'], 403);

        // Validar fecha dentro del período
        $periodo = PeriodoAcademico::findOrFail((int)$data['periodo_id']);
        if (!($periodo->fecha_inicio->toDateString() <= $data['fecha'] && $data['fecha'] <= $periodo->fecha_fin->toDateString())) {
            return response()->json([
                'ok'=>false,
                'message'=>'La fecha del evento está fuera del rango del período.',
                'rango'=>[$periodo->fecha_inicio->toDateString(), $periodo->fecha_fin->toDateString()],
            ], 422);
        }

        $codigo = $data['codigo'] ?: ('EVT-'.now()->format('YmdHis').'-'.$user->id);

        $evento = VmEvento::create([
            'periodo_id'     => $data['periodo_id'],
            'targetable_type'=> $type, // alias morphMap
            'targetable_id'  => $targetId,
            'codigo'         => $codigo,
            'titulo'         => $data['titulo'],
            'fecha'          => $data['fecha'],
            'hora_inicio'    => $data['hora_inicio'],
            'hora_fin'       => $data['hora_fin'],
            'requiere_inscripcion' => (bool) ($data['requiere_inscripcion'] ?? false),
            'cupo_maximo'    => $data['cupo_maximo'] ?? null,
            'estado'         => 'PLANIFICADO',
        ]);

        return response()->json(['ok'=>true,'data'=>new VmEventoResource($evento)], 201);
    }
}
