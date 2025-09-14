<?php

namespace App\Http\Controllers\Api\Coordinador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinador\AsignarEstudianteAEpRequest;
use App\Models\CoordinadorEpSede;
use App\Models\Estudiante;
use App\Models\PeriodoAcademico;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AsignacionEpController extends Controller
{
    /**
     * Asigna al estudiante a la EP del coordinador autenticado (periodo actual).
     * Body puede traer: { "estudiante_id": 123 } o { "codigo": "E250123" }
     */
    public function assign(AsignarEstudianteAEpRequest $request): JsonResponse
    {
        // 1) Resuelve el periodo actual
        $periodo = PeriodoAcademico::query()->actual()->first();
        if (! $periodo) {
            throw new RuntimeException('No existe periodo académico marcado como actual.');
        }

        // 2) Resuelve la EP del coordinador autenticado en el periodo actual
        $user = $request->user();
        $coordinacion = CoordinadorEpSede::query()
            ->where('persona_id', $user->persona_id) // ajusta si tu relación es distinta
            ->where('periodo_id', $periodo->id)
            ->where('activo', true)
            ->first();

        if (! $coordinacion) {
            return response()->json([
                'message' => 'No se encontró una coordinación activa para este usuario en el periodo actual.'
            ], 409);
        }

        // 3) Resuelve el estudiante
        $estudiante = null;
        if ($request->filled('estudiante_id')) {
            $estudiante = Estudiante::find($request->integer('estudiante_id'));
        } else {
            $codigo = $request->string('codigo')->toString();
            $estudiante = Estudiante::where('codigo', $codigo)->first();
        }

        if (! $estudiante) {
            return response()->json(['message' => 'Estudiante no encontrado.'], 404);
        }

        // 4) Asigna automáticamente la EP (sin pedir ep_sede_id al coordinador)
        $epSedeId = (int) $coordinacion->ep_sede_id;

        if ((int) $estudiante->ep_sede_id === $epSedeId) {
            return response()->json([
                'message'    => 'El estudiante ya está asignado a esta EP.',
                'estudiante' => $estudiante->only(['id','codigo','ep_sede_id'])
            ], 200);
        }

        DB::transaction(function () use ($estudiante, $epSedeId) {
            // si quieres auditar el cambio, guarda $estudiante->getOriginal('ep_sede_id')
            $estudiante->update(['ep_sede_id' => $epSedeId]);
        });

        // Refresh para devolver datos actualizados
        $estudiante->refresh();

        return response()->json([
            'message'    => 'Estudiante asignado a la EP del coordinador correctamente.',
            'periodo'    => ['id' => $periodo->id, 'nombre' => $periodo->nombre ?? null],
            'ep_sede_id' => $epSedeId,
            'estudiante' => $estudiante->only(['id','codigo','ep_sede_id'])
        ], 200);
    }
}
