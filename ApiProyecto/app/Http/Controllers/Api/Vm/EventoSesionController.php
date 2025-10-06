<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vm\SesionBatchRequest;
use App\Http\Resources\Vm\VmSesionResource;
use App\Models\VmEvento;
use App\Services\Auth\EpScopeService;
use App\Services\Vm\SesionBatchService;
use App\Support\DateList;
use Illuminate\Http\JsonResponse;

class EventoSesionController extends Controller
{
    /** POST /api/vm/eventos/{evento}/sesiones/batch */
    public function storeBatch(VmEvento $evento, SesionBatchRequest $request): JsonResponse
    {
        $user = $request->user();
        $evento->load('periodo');

        // Alcance según target (alias morph guardado)
        $ok = match ($evento->getRawOriginal('targetable_type')) {
            'ep_sede'  => EpScopeService::userManagesEpSede($user->id, (int)$evento->targetable_id),
            'sede'     => EpScopeService::userManagesSede($user->id,    (int)$evento->targetable_id),
            'facultad' => EpScopeService::userManagesFacultad($user->id,(int)$evento->targetable_id),
            default    => false,
        };
        if (!$ok) return response()->json(['ok'=>false,'message'=>'No autorizado para el target del evento.'], 403);

        // Fechas dentro del período del evento
        $fechas = DateList::fromBatchPayload($request->validated());
        $ini = $evento->periodo->fecha_inicio->toDateString();
        $fin = $evento->periodo->fecha_fin->toDateString();

        $fuera = $fechas->filter(fn($f) => !($ini <= $f && $f <= $fin))->values();
        if ($fuera->isNotEmpty()) {
            return response()->json([
                'ok'=>false,
                'message'=>'Hay fechas fuera del período del evento.',
                'rango'=>[$ini,$fin],
                'fechas_fuera'=>$fuera,
            ], 422);
        }

        $created = SesionBatchService::createFor($evento, $request->validated());

        return response()->json(['ok'=>true,'data'=>VmSesionResource::collection($created)], 201);
    }
}
