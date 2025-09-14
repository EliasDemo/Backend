<?php

namespace App\Http\Controllers\Api\Facultad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facultad\FacultadStoreRequest;
use App\Http\Requests\Facultad\FacultadUpdateRequest;
use App\Http\Resources\Facultad\FacultadResource;
use App\Models\Facultad;
use App\Services\Facultad\FacultadService;
use Illuminate\Http\Request;

class FacultadController extends Controller
{
    public function __construct(private readonly FacultadService $service) {}

    /** GET /facultades */
    public function index(Request $request)
    {
        $q = $this->service->baseQuery();

        if ($request->filled('q')) {
            $term = trim($request->query('q'));
            $q->where(function ($qq) use ($term) {
                $qq->where('nombre', 'like', "%{$term}%")
                   ->orWhere('codigo', 'like', "%{$term}%");
            });
        }

        if ($request->boolean('only_suspended')) {
            $q->where('esta_suspendida', true);
        } elseif (! $request->boolean('include_suspended')) {
            $q->where('esta_suspendida', false);
        }

        $sort = $request->query('sort', 'nombre');
        $dir  = $request->query('direction', 'asc');
        $per  = (int) $request->query('per_page', 15);

        $q->orderBy($sort, $dir);

        return FacultadResource::collection(
            $q->paginate($per)->appends($request->query())
        );
    }

    /** POST /facultades */
    public function store(FacultadStoreRequest $request)
    {
        $row = $this->service->create($request->validated());

        return (new FacultadResource($row))
            ->additional(['message' => 'Facultad creada correctamente'])
            ->response()
            ->setStatusCode(201);
    }

    /** PATCH /facultades/{facultad} */
    public function update(FacultadUpdateRequest $request, Facultad $facultad)
    {
        $row = $this->service->update($facultad, $request->validated());

        return (new FacultadResource($row))
            ->additional(['message' => 'Facultad actualizada correctamente']);
    }

    /** PATCH /facultades/{facultad}/suspender */
    public function suspender(Facultad $facultad)
    {
        $row = $this->service->suspender($facultad);

        return (new FacultadResource($row))
            ->additional(['message' => 'Facultad suspendida']);
    }

    /** PATCH /facultades/{facultad}/restaurar */
    public function restaurar(Facultad $facultad)
    {
        $row = $this->service->restaurar($facultad);

        return (new FacultadResource($row))
            ->additional(['message' => 'Facultad restaurada']);
    }
}
