<?php

namespace App\Http\Controllers\Api\Sede;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sede\SedeStoreRequest;
use App\Http\Requests\Sede\SedeUpdateRequest;
use App\Http\Resources\Sede\SedeResource;
use App\Models\Sede;
use App\Services\Sede\SedeService;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    public function __construct(private readonly SedeService $service) {}

    public function index(Request $request)
    {
        $q = $this->service->baseQuery();

        if ($request->boolean('only_suspended')) {
            $q->where('esta_suspendida', true);
        } elseif (! $request->boolean('include_suspended')) {
            $q->where('esta_suspendida', false);
        }

        if ($search = trim((string)$request->query('q', ''))) {
            $q->where('nombre', 'like', "%{$search}%");
        }

        $allowedSort = ['nombre','created_at','updated_at'];
        $sort = in_array($request->query('sort'), $allowedSort, true) ? $request->query('sort') : null;
        $direction = strtolower((string)$request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sort) {
            $q->orderBy($sort, $direction);
        } else {
            $q->orderByDesc('es_principal')->orderBy('nombre');
        }

        $perPage = (int)($request->query('per_page', 15));
        $sedes = $q->paginate($perPage)->appends($request->query());

        return \App\Http\Resources\Sede\SedeResource::collection($sedes);
    }

    public function store(SedeStoreRequest $request)
    {
        $sede = $this->service->create($request->validated());

        return (new SedeResource($sede))
            ->additional(['message' => 'Sede creada correctamente'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(SedeUpdateRequest $request, Sede $sede)
    {
        $sede = $this->service->update($sede, $request->validated());

        return (new SedeResource($sede))
            ->additional(['message' => 'Sede actualizada correctamente']);
    }

    public function makePrincipal(Sede $sede)
    {
        $sede = $this->service->makePrincipal($sede);

        return (new SedeResource($sede))
            ->additional(['message' => 'Sede marcada como principal']);
    }

    public function suspend(Sede $sede)
    {
        $sede = $this->service->suspend($sede);

        return (new SedeResource($sede))
            ->additional(['message' => 'Sede suspendida correctamente']);
    }

    public function restore(Sede $sede)
    {
        $sede = $this->service->restore($sede);

        return (new SedeResource($sede))
            ->additional(['message' => 'Sede restaurada correctamente']);
    }
}
