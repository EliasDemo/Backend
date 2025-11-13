<?php

namespace App\Http\Controllers\Api\EpSede;

use App\Http\Controllers\Controller;
use App\Http\Requests\EpSede\AssignEpSedeStaffRequest;
use App\Http\Requests\EpSede\DelegateEpSedeStaffRequest;
use App\Http\Requests\EpSede\ReinstateEpSedeStaffRequest;
use App\Http\Requests\EpSede\UnassignEpSedeStaffRequest;
use App\Services\Auth\EpScopeService;
use App\Services\EpSede\StaffAssignmentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EpSedeStaffController extends Controller
{
    /**
     * Autoriza acciones sobre staff según:
     * - rol objetivo (COORDINADOR / ENCARGADO)
     * - rol/permisos del actor (ADMIN, COORDINADOR, etc.)
     * - EP-Sede objetivo
     */
    protected function authorizeStaffAction(Request $request, int $epSedeId, string $role): void
    {
        $user = $request->user();
        $role = strtoupper($role);

        switch ($role) {
            case 'COORDINADOR':
                // Solo quien tenga permiso de gestionar coordinadores
                if (!$user->can('ep.staff.manage.coordinador')) {
                    abort(Response::HTTP_FORBIDDEN, 'No autorizado para gestionar coordinadores.');
                }

                // ADMIN puede gestionar cualquier EP-Sede,
                // otros: deben "gestionar" esa EP-Sede según EpScopeService
                if (
                    !$user->hasRole('ADMINISTRADOR') &&
                    !EpScopeService::userManagesEpSede((int) $user->id, $epSedeId)
                ) {
                    abort(Response::HTTP_FORBIDDEN, 'No autorizado para esta EP-Sede.');
                }
                break;

            case 'ENCARGADO':
                // Necesita permiso para gestionar encargados
                if (!$user->can('ep.staff.manage.encargado')) {
                    abort(Response::HTTP_FORBIDDEN, 'No autorizado para gestionar encargados.');
                }

                // ADMIN: cualquier EP-Sede
                // COORDINADOR: solo EP-Sedes a las que pertenece (Expediente ACTIVO)
                if (
                    !$user->hasRole('ADMINISTRADOR') &&
                    !EpScopeService::userBelongsToEpSede((int) $user->id, $epSedeId)
                ) {
                    abort(Response::HTTP_FORBIDDEN, 'No autorizado para esta EP-Sede.');
                }
                break;

            default:
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Rol no soportado.');
        }
    }

    public function current(Request $request, int $epSedeId, StaffAssignmentService $service)
    {
        $user   = $request->user();
        $userId = (int) $user->id;

        // ADMIN ve cualquier EP-Sede; otros solo donde EpScopeService lo permita
        if (
            !$user->hasRole('ADMINISTRADOR') &&
            !EpScopeService::userManagesEpSede($userId, $epSedeId)
        ) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'ep_sede_id' => $epSedeId,
            'staff'      => $service->current($epSedeId),
        ]);
    }

    public function assign(AssignEpSedeStaffRequest $request, int $epSedeId, StaffAssignmentService $service)
    {
        $role = strtoupper((string) $request->string('role'));

        // Autoriza según rol a asignar y EP-Sede
        $this->authorizeStaffAction($request, $epSedeId, $role);

        $payload = $service->assign(
            epSedeId: $epSedeId,
            role: $role,
            newUserId: (int) $request->integer('user_id'),
            vigenteDesde: $request->input('vigente_desde'),
            exclusive: $request->exclusive(),
            actorId: $request->user()->id,
            motivo: $request->input('motivo')
        );

        return response()->json($payload, Response::HTTP_OK);
    }

    public function unassign(UnassignEpSedeStaffRequest $request, int $epSedeId, StaffAssignmentService $service)
    {
        $role = strtoupper((string) $request->string('role'));

        // Autoriza según rol a desasignar y EP-Sede
        $this->authorizeStaffAction($request, $epSedeId, $role);

        $payload = $service->unassign(
            epSedeId: $epSedeId,
            role: $role,
            actorId: $request->user()->id,
            motivo: $request->input('motivo')
        );

        return response()->json($payload, Response::HTTP_OK);
    }

    public function reinstate(ReinstateEpSedeStaffRequest $request, int $epSedeId, StaffAssignmentService $service)
    {
        $role = strtoupper((string) $request->string('role'));

        // Autoriza según rol a reincorporar y EP-Sede
        $this->authorizeStaffAction($request, $epSedeId, $role);

        $payload = $service->reinstate(
            epSedeId: $epSedeId,
            role: $role,
            userId: (int) $request->integer('user_id'),
            vigenteDesde: $request->input('vigente_desde'),
            exclusive: true,
            actorId: $request->user()->id,
            motivo: $request->input('motivo')
        );

        return response()->json($payload, Response::HTTP_OK);
    }

    public function delegate(DelegateEpSedeStaffRequest $request, int $epSedeId, StaffAssignmentService $service)
    {
        $role = strtoupper((string) $request->string('role'));

        // Autoriza según rol a delegar y EP-Sede
        $this->authorizeStaffAction($request, $epSedeId, $role);

        $payload = $service->delegate(
            epSedeId: $epSedeId,
            role: $role,
            userId: (int) $request->integer('user_id'),
            desde: (string) $request->string('desde'),
            hasta: (string) $request->string('hasta'),
            actorId: $request->user()->id,
            motivo: $request->input('motivo')
        );

        return response()->json($payload, Response::HTTP_OK);
    }

    public function history(Request $request, int $epSedeId, StaffAssignmentService $service)
    {
        $user   = $request->user();
        $userId = (int) $user->id;

        if (
            !$user->hasRole('ADMINISTRADOR') &&
            !EpScopeService::userManagesEpSede($userId, $epSedeId)
        ) {
            return response()->json(['message' => 'No autorizado.'], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'ep_sede_id' => $epSedeId,
            'history'    => $service->history($epSedeId),
        ]);
    }
}
