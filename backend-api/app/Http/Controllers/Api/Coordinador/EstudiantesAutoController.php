<?php

namespace App\Http\Controllers\Api\Coordinador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinador\UpsertAsignarEstudianteRequest;
use App\Models\CoordinadorEpSede;
use App\Models\Estudiante;
use App\Models\Persona;
use App\Models\PeriodoAcademico;
use App\Services\Auth\UserProvisioningService;
use App\Services\Personas\PersonaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EstudiantesAutoController extends Controller
{
    public function __construct(
        private readonly PersonaService $personas,
        private readonly UserProvisioningService $provision
    ) {}

    /**
     * Crea o actualiza un estudiante y lo asigna a la EP del coordinador autenticado.
     * - Si existe (por codigo/dni/email_inst), lo actualiza y reasigna si hace falta.
     * - Si no existe, crea Persona + Estudiante + User('Estudiante') y asigna a su EP.
     */
    public function upsertAndAssign(UpsertAsignarEstudianteRequest $request): JsonResponse
    {
        $user = $request->user();

        // 1) Periodo actual
        $periodo = PeriodoAcademico::query()->actual()->first();
        if (! $periodo) {
            throw new RuntimeException('No existe periodo académico marcado como actual.');
        }

        // 2) EP del coordinador en el periodo actual
        $coordinacion = CoordinadorEpSede::query()
            ->where('persona_id', $user->persona_id)   // ajusta si tu User->persona_id es diferente
            ->where('periodo_id', $periodo->id)
            ->where('activo', true)
            ->first();

        if (! $coordinacion) {
            return response()->json([
                'message' => 'No se encontró una coordinación activa para este usuario en el periodo actual.'
            ], 409);
        }
        $epSedeId = (int) $coordinacion->ep_sede_id;

        // 3) Intentar localizar estudiante existente por prioridad: codigo > dni > email_inst
        $codigo   = $request->string('codigo')->toString();
        $dni      = $request->string('dni')->toString();
        $emailIns = $request->string('email_institucional')->toString();

        $estudiante = null;
        if ($codigo) {
            $estudiante = Estudiante::where('codigo', strtoupper($codigo))->first();
        }
        if (! $estudiante && ($dni || $emailIns)) {
            $persona = Persona::query()
                ->when($dni, fn($q) => $q->orWhere('dni', $dni))
                ->when($emailIns, fn($q) => $q->orWhere('email_institucional', strtolower($emailIns)))
                ->first();
            if ($persona) {
                $estudiante = Estudiante::where('persona_id', $persona->id)->first();
            }
        }

        // 4) Datos de persona y académicos (sin ep_sede_id; se impone el del coordinador)
        $personaData = array_filter([
            'dni'                 => $dni ?: null,
            'apellidos'           => $request->input('apellidos'),
            'nombres'             => $request->input('nombres'),
            'email_institucional' => $emailIns ?: null,
            'email_personal'      => $request->input('email_personal'),
            'celular'             => $request->input('celular'),
            'sexo'                => $request->input('sexo'),
            'fecha_nacimiento'    => $request->input('fecha_nacimiento'),
        ], fn($v) => !is_null($v) && $v !== '');

        $academic = array_filter([
            'ingreso_periodo_id'  => $request->input('ingreso_periodo_id'),
            'ciclo_actual'        => $request->input('ciclo_actual'),
            'cohorte_codigo'      => $request->input('cohorte_codigo'),
        ], fn($v) => !is_null($v) && $v !== '');

        // 5) Transacción: crear/actualizar persona + (crear si falta) estudiante + usuario
        $created = false;

        $result = DB::transaction(function () use ($estudiante, $personaData, $academic, $epSedeId, &$created) {
            if ($estudiante) {
                // a) Actualiza Persona vía upsert y vinculación existente
                $persona = $this->personas->upsertFromDTO($personaData + ['id' => $estudiante->persona_id]);

                // b) Reasigna EP si es distinta
                if ((int) $estudiante->ep_sede_id !== (int) $epSedeId) {
                    $estudiante->update(['ep_sede_id' => $epSedeId]);
                }

                // c) (Opcional) Actualiza datos académicos
                $estudiante->fill($academic);
                $estudiante->save();

                // d) Garantiza usuario con rol Estudiante
                [$user, $passwordTemporal] = $this->provision->provisionForPersona($persona, 'Estudiante');

                return compact('persona','estudiante','user','passwordTemporal');
            }

            // No existe: crear todo
            $persona = $this->personas->upsertFromDTO($personaData);

            // Código: usa provisto o genera
            $codigo = $academic['codigo'] ?? ('E' . date('y') . random_int(1000, 9999));
            do {
                $candidate = strtoupper(trim($codigo));
                $exists = Estudiante::where('codigo', $candidate)->exists();
                if ($exists) { $codigo = 'E' . date('y') . random_int(1000, 9999); }
            } while ($exists);

            $estudianteNew = Estudiante::create([
                'persona_id'         => $persona->id,
                'ep_sede_id'         => $epSedeId,
                'ingreso_periodo_id' => $academic['ingreso_periodo_id'] ?? null,
                'estado'             => 'ACTIVO',
                'ciclo_actual'       => $academic['ciclo_actual'] ?? null,
                'cohorte_codigo'     => $academic['cohorte_codigo'] ?? null,
                'codigo'             => $candidate,
            ]);

            [$user, $passwordTemporal] = $this->provision->provisionForPersona($persona, 'Estudiante');
            $created = true;

            return ['persona' => $persona, 'estudiante' => $estudianteNew, 'user' => $user, 'passwordTemporal' => $passwordTemporal];
        });

        $payload = [
            'message'    => $created
                ? 'Estudiante creado y asignado automáticamente a la EP del coordinador.'
                : 'Estudiante actualizado/asignado a la EP del coordinador.',
            'periodo'    => ['id' => $periodo->id, 'nombre' => $periodo->nombre ?? null],
            'ep_sede_id' => $epSedeId,
            'persona'    => $result['persona'],
            'user'       => [
                'id'       => $result['user']->id,
                'username' => $result['user']->username,
                'roles'    => $result['user']->getRoleNames()->values()->all(),
            ],
            'password_temporal' => $result['passwordTemporal'], // muéstrala solo aquí
            'estudiante' => [
                'id'         => $result['estudiante']->id,
                'codigo'     => $result['estudiante']->codigo,
                'ep_sede_id' => $result['estudiante']->ep_sede_id,
                'estado'     => $result['estudiante']->estado,
            ],
        ];

        return response()->json($payload, $created ? 201 : 200);
    }
}
