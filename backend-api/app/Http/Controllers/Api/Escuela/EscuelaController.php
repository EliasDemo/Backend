<?php

namespace App\Http\Controllers\Api\Escuela;

use App\Http\Controllers\Controller;
use App\Http\Requests\Escuela\EscuelaStoreRequest;
use App\Http\Requests\Escuela\EscuelaUpdateRequest;
use App\Http\Resources\Escuela\EscuelaResource;
use App\Models\EscuelaProfesional;
use App\Services\Escuela\EscuelaService;
use Illuminate\Http\Request;

class EscuelaController extends Controller
{
    public function __construct(private readonly EscuelaService $service) {}

    /** GET /escuelas */
    public function index(Request $request)
    {
        $q = $this->service->baseQuery();

        if ($request->filled('facultad_id')) {
            $q->where('escuelas_profesionales.facultad_id', (int) $request->query('facultad_id'));
        }

        if ($request->filled('q')) {
            $term = trim($request->query('q'));
            $q->where(function ($qq) use ($term) {
                $qq->where('escuelas_profesionales.nombre', 'like', "%{$term}%")
                   ->orWhere('escuelas_profesionales.codigo', 'like', "%{$term}%");
            });
        }

        if ($request->boolean('only_suspended')) {
            $q->where('escuelas_profesionales.esta_suspendida', true);
        } elseif (! $request->boolean('include_suspended')) {
            $q->where('escuelas_profesionales.esta_suspendida', false);
        }

        $sort = $request->query('sort', 'escuelas_profesionales.nombre');
        $dir  = $request->query('direction', 'asc');
        $per  = (int) $request->query('per_page', 15);

        $q->orderBy($sort, $dir);

        return EscuelaResource::collection(
            $q->paginate($per)->appends($request->query())
        );
    }

    /** POST /escuelas */
    public function store(EscuelaStoreRequest $request)
    {
        $row = $this->service->create($request->validated());

        return (new EscuelaResource($row))
            ->additional(['message' => 'Escuela creada correctamente'])
            ->response()
            ->setStatusCode(201);
    }

    /** PATCH /escuelas/{escuela} */
    public function update(EscuelaUpdateRequest $request, EscuelaProfesional $escuela)
    {
        $row = $this->service->update($escuela, $request->validated());

        return (new EscuelaResource($row))
            ->additional(['message' => 'Escuela actualizada correctamente']);
    }

    /** PATCH /escuelas/{escuela}/suspender */
    public function suspender(EscuelaProfesional $escuela)
    {
        $row = $this->service->suspender($escuela);

        return (new EscuelaResource($row))
            ->additional(['message' => 'Escuela suspendida']);
    }

    /** PATCH /escuelas/{escuela}/restaurar */
    public function restaurar(EscuelaProfesional $escuela)
    {
        $row = $this->service->restaurar($escuela);

        return (new EscuelaResource($row))
            ->additional(['message' => 'Escuela restaurada']);
    }
}
