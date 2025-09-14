<?php

namespace App\Http\Controllers\Api\Periodo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Periodo\PeriodoStoreRequest;
use App\Http\Requests\Periodo\PeriodoUpdateRequest;
use App\Http\Resources\Periodo\PeriodoResource;
use App\Models\PeriodoAcademico;
use App\Services\Periodo\PeriodoService;
use Illuminate\Http\Request;

class PeriodoController extends Controller
{
    public function __construct(private readonly PeriodoService $service) {}

    /** GET /periodos?anio=&ciclo=&estado=&vigentes_en=YYYY-MM-DD&per_page=15 */
    public function index(Request $request)
    {
        $q = $this->service->baseQuery();

        if ($request->filled('anio'))   $q->anio((int) $request->query('anio'));
        if ($request->filled('ciclo'))  $q->ciclo((int) $request->query('ciclo'));
        if ($request->filled('estado')) $q->where('estado', $request->query('estado'));

        if ($f = $request->query('vigentes_en')) {
            $q->vigentesEn($f);
        }

        $per = (int) $request->query('per_page', 15);
        $q->orderBy('anio', 'desc')->orderBy('ciclo', 'desc');

        return PeriodoResource::collection($q->paginate($per)->appends($request->query()));
    }

    /** GET /periodos/actual */
    public function actual()
    {
        $row = PeriodoAcademico::actual()->first();
        return $row ? new PeriodoResource($row) : response()->json(['message' => 'No hay periodo actual'], 404);
    }

    /** POST /periodos */
    public function store(PeriodoStoreRequest $request)
    {
        $row = $this->service->create($request->validated());
        return (new PeriodoResource($row))
            ->additional(['message' => 'Periodo creado correctamente'])
            ->response()->setStatusCode(201);
    }

    /** PATCH /periodos/{periodo} */
    public function update(PeriodoUpdateRequest $request, PeriodoAcademico $periodo)
    {
        $row = $this->service->update($periodo, $request->validated());
        return (new PeriodoResource($row))
            ->additional(['message' => 'Periodo actualizado correctamente']);
    }

    /** PATCH /periodos/{periodo}/marcar-actual */
    public function marcarActual(PeriodoAcademico $periodo)
    {
        $this->service->marcarComoActual($periodo);
        return (new PeriodoResource($periodo->fresh()))
            ->additional(['message' => 'Periodo marcado como actual']);
    }
}
