<?php

namespace App\Http\Controllers\Api\Vm;

use App\Http\Controllers\Controller;
use App\Models\VmSesion;
use App\Models\VmQrToken;
use App\Models\VmAsistencia;
use App\Services\Vm\AsistenciaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AsistenciasController extends Controller
{
    public function __construct(private AsistenciaService $svc) {}

    // ───────────────────────── Ventanas (30 min) ─────────────────────────

    // POST /api/vm/sesiones/{sesion}/qr  (staff)
    public function generarQr(Request $request, VmSesion $sesion): JsonResponse
    {
        $data = $request->validate([
            'max_usos' => ['nullable','integer','min:1'],
            'lat'      => ['nullable','numeric','between:-90,90'],
            'lng'      => ['nullable','numeric','between:-180,180'],
            'radio_m'  => ['nullable','integer','min:10','max:5000'],
        ]);

        $geo = (isset($data['lat'], $data['lng'], $data['radio_m']))
            ? ['lat'=>$data['lat'], 'lng'=>$data['lng'], 'radio_m'=>$data['radio_m']]
            : null;

        $token = $this->svc->generarToken(
            sesion: $sesion,
            tipo: 'QR',
            geo: $geo,
            maxUsos: $data['max_usos'] ?? null,
            creadoPor: $request->user()->id
        );

        return response()->json([
            'ok' => true,
            'code' => 'QR_OPENED',
            'data' => [
                'token'       => $token->token,
                'usable_from' => $token->usable_from,
                'expires_at'  => $token->expires_at,
                'geo'         => $geo,
            ],
        ], 201);
    }

    // POST /api/vm/sesiones/{sesion}/activar-manual  (staff)
    public function activarManual(Request $request, VmSesion $sesion): JsonResponse
    {
        $token = $this->svc->generarToken(
            sesion: $sesion,
            tipo: 'MANUAL',
            geo: null,
            maxUsos: null,
            creadoPor: $request->user()->id
        );

        return response()->json([
            'ok' => true,
            'code' => 'MANUAL_OPENED',
            'data' => [
                'usable_from' => $token->usable_from,
                'expires_at'  => $token->expires_at,
            ],
        ], 201);
    }

    // ───────────────────────── Check-in ─────────────────────────

    // POST /api/vm/sesiones/{sesion}/check-in/qr  (alumno)
    public function checkInPorQr(Request $request, VmSesion $sesion): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required','string','size:32'],
            'lat'   => ['nullable','numeric','between:-90,90'],
            'lng'   => ['nullable','numeric','between:-180,180'],
        ]);

        $token = VmQrToken::where('sesion_id', $sesion->id)
            ->where('tipo', 'QR')
            ->where('token', $data['token'])
            ->firstOrFail();

        $this->svc->checkVentana($token);
        $this->svc->checkGeofence($token, $data['lat'] ?? null, $data['lng'] ?? null);

        $epSedeId = $this->svc->epSedeIdDesdeSesion($sesion);
        $exp = $this->svc->resolverExpedientePorUser($request->user(), $epSedeId);
        if (!$exp) {
            return $this->fail('DIFFERENT_EP_SEDE', 'No perteneces a la EP_SEDE de la sesión.', 422, ['ep_sede_id'=>$epSedeId]);
        }

        $a = $this->svc->upsertAsistencia(
            sesion: $sesion,
            exp: $exp,
            metodo: 'QR',
            token: $token,
            meta: [
                'lat'=>$data['lat'] ?? null,
                'lng'=>$data['lng'] ?? null,
                'ip'=>$request->ip(),
                'ua'=>$request->userAgent(),
            ]
        );

        return response()->json([
            'ok'=>true,
            'code'=>'CHECKED_IN',
            'data'=>[
                'asistencia'=>$a,
                'ventana_fin'=>$token->expires_at,
            ]
        ], 201);
    }

    // POST /api/vm/sesiones/{sesion}/check-in/manual  (staff)
    public function checkInManual(Request $request, VmSesion $sesion): JsonResponse
    {
        $data = $request->validate([
            'identificador' => ['required','string','max:191'], // DNI o Código
        ]);

        $ventana = VmQrToken::where('sesion_id', $sesion->id)
            ->where('tipo','MANUAL')->where('activo', true)
            ->latest('id')->first();

        if (!$ventana) {
            return $this->fail('VENTANA_NO_ACTIVA', 'Activa primero el llamado manual (30 min).', 422);
        }
        $this->svc->checkVentana($ventana);

        $epSedeId = $this->svc->epSedeIdDesdeSesion($sesion);
        $exp = $this->svc->resolverExpedientePorIdentificador($data['identificador'], $epSedeId);

        if (!$exp) {
            return $this->fail('NO_ENCONTRADO', 'No se encontró expediente por DNI/Código en esta EP_SEDE.', 422, [
                'identificador' => $data['identificador'],
                'ep_sede_id'    => $epSedeId
            ]);
        }

        $a = $this->svc->upsertAsistencia(
            sesion: $sesion,
            exp: $exp,
            metodo: 'MANUAL',
            token: $ventana,
            meta: ['registrado_por'=>$request->user()->id, 'ip'=>$request->ip()]
        );

        return response()->json(['ok'=>true,'code'=>'CHECKED_IN','data'=>['asistencia'=>$a]], 201);
    }

    // ───────────────────────── Consulta / Reporte ─────────────────────────

    // GET /api/vm/sesiones/{sesion}/asistencias  (staff)
    public function listarAsistencias(Request $request, VmSesion $sesion): JsonResponse
    {
        $rows = VmAsistencia::query()
            ->with(['expediente.user:id,first_name,last_name,doc_numero'])
            ->where('sesion_id', $sesion->id)
            ->orderByDesc('check_in_at')
            ->get()
            ->map(function (VmAsistencia $a) {
                return [
                    'id'          => $a->id,
                    'metodo'      => $a->metodo,
                    'estado'      => $a->estado,
                    'check_in_at' => $a->check_in_at,
                    'minutos'     => $a->minutos_validados,
                    'codigo'      => $a->expediente->codigo_estudiante ?? null,
                    'dni'         => $a->expediente->user->doc_numero ?? null,
                    'nombres'     => $a->expediente->user->first_name ?? null,
                    'apellidos'   => $a->expediente->user->last_name ?? null,
                ];
            });

        return response()->json(['ok'=>true,'data'=>$rows], 200);
    }

    // GET /api/vm/sesiones/{sesion}/asistencias/reporte?format=csv|json  (staff)
    public function reporte(Request $request, VmSesion $sesion)
    {
        $format = $request->query('format','json');

        $query = VmAsistencia::query()
            ->select([
                'vm_asistencias.id',
                'vm_asistencias.metodo',
                'vm_asistencias.check_in_at',
                'vm_asistencias.estado',
                'vm_asistencias.minutos_validados',
                'expedientes_academicos.codigo_estudiante',
                'users.doc_numero as dni',
                'users.first_name',
                'users.last_name',
            ])
            ->join('expedientes_academicos','expedientes_academicos.id','=','vm_asistencias.expediente_id')
            ->join('users','users.id','=','expedientes_academicos.user_id')
            ->where('vm_asistencias.sesion_id', $sesion->id)
            ->orderBy('users.last_name');

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="reporte_sesion_'.$sesion->id.'.csv"',
            ];
            $callback = function () use ($query) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Nombres','Apellidos','Código','DNI','Método','Check-in','Estado','MinutosValidados']);
                $query->chunk(500, function ($chunk) use ($out) {
                    foreach ($chunk as $r) {
                        fputcsv($out, [
                            $r->first_name,
                            $r->last_name,
                            $r->codigo_estudiante,
                            $r->dni,
                            $r->metodo,
                            $r->check_in_at,
                            $r->estado,
                            $r->minutos_validados,
                        ]);
                    }
                });
                fclose($out);
            };
            return new StreamedResponse($callback, 200, $headers);
        }

        return response()->json(['ok'=>true,'data'=>$query->get()], 200);
    }

    // POST /api/vm/sesiones/{sesion}/validar  (staff)
    public function validarAsistencias(Request $request, VmSesion $sesion): JsonResponse
    {
        $minSesion = $this->svc->minutosSesion($sesion);

        $payload = $request->validate([
            'asistencias'          => ['nullable','array'],
            'asistencias.*'        => ['integer','exists:vm_asistencias,id'],
            'crear_registro_horas' => ['nullable','boolean'],
        ]);

        $ids = $payload['asistencias'] ?? [];
        $crearReg = array_key_exists('crear_registro_horas', $payload)
            ? (bool)$payload['crear_registro_horas']
            : true; // por defecto sí crea registros

        $q = VmAsistencia::where('sesion_id', $sesion->id)
            ->when($ids, fn($qq) => $qq->whereIn('id',$ids));

        $total = 0;
        $svc = $this->svc;

        DB::transaction(function () use ($q, $minSesion, $crearReg, $svc, &$total) {
            $q->lockForUpdate()->get()->each(function (VmAsistencia $a) use ($minSesion, $crearReg, $svc, &$total) {
                $svc->validarAsistencia($a, $minSesion, $crearReg);
                $total++;
            });
        });

        return response()->json([
            'ok'=>true,
            'code'=>'VALIDATED',
            'data'=>[
                'validadas'=>$total,
                'minutos_por_asistencia'=>$minSesion,
                'registro_horas_creado'=>$crearReg,
            ]
        ], 200);
    }

    // ───────────────────────── Helper ─────────────────────────
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
