<?php

namespace App\Http\Controllers\Api\EpSede;

use App\Http\Controllers\Controller;
use App\Http\Requests\EpSede\EpSedeStoreRequest;
use App\Http\Requests\EpSede\EpSedeUpdateRequest;
use App\Http\Resources\EpSede\EpSedeResource;
use App\Models\EpSede;
use App\Services\EpSede\EpSedeService;
use Illuminate\Http\Request;

class EpSedeController extends Controller
{
    public function __construct(private readonly EpSedeService $service) {}

    /** GET /ep-sede?ep_id=&sede_id=&at=&only_past=1&per_page=15 */
    public function index(Request $request)
    {
        $q = $this->service->baseQuery();

        if ($request->filled('sede_id')) $q->where('sede_id', (int) $request->query('sede_id'));
        if ($request->filled('ep_id'))   $q->where('escuela_profesional_id', (int) $request->query('ep_id'));

        if ($at = $request->query('at')) {
            $f = \Carbon\Carbon::parse($at)->toDateString();
            $q->where(fn($qq)=>$qq
                ->where(fn($q1)=>$q1->whereNull('vigente_desde')->orWhere('vigente_desde','<=',$f))
                ->where(fn($q2)=>$q2->whereNull('vigente_hasta')->orWhere('vigente_hasta','>=',$f))
            );
        } elseif ($request->boolean('only_past')) {
            $hoy = now()->toDateString();
            $q->whereNot(fn($qq)=>$qq
                ->where(fn($q1)=>$q1->whereNull('vigente_desde')->orWhere('vigente_desde','<=',$hoy))
                ->where(fn($q2)=>$q2->whereNull('vigente_hasta')->orWhere('vigente_hasta','>=',$hoy))
            );
        }

        return EpSedeResource::collection(
            $q->paginate((int)$request->query('per_page',15))->appends($request->query())
        );
    }

    /** POST /ep-sede */
    public function store(EpSedeStoreRequest $request)
    {
        $row = $this->service->create($request->validated());
        return (new EpSedeResource($row))
            ->additional(['message' => 'Oferta creada correctamente'])
            ->response()->setStatusCode(201);
    }

    /** PATCH /ep-sede/{ep_sede} */
    public function update(EpSedeUpdateRequest $request, EpSede $ep_sede)
    {
        $row = $this->service->update($ep_sede, $request->validated());
        return (new EpSedeResource($row))
            ->additional(['message' => 'Oferta actualizada correctamente']);
    }

    /** PATCH /ep-sede/{ep_sede}/cerrar */
    public function close(Request $request, EpSede $ep_sede)
    {
        $request->validate(['vigente_hasta' => ['nullable','date']]);
        $row = $this->service->close($ep_sede, $request->input('vigente_hasta'));
        return (new EpSedeResource($row))
            ->additional(['message' => 'Oferta cerrada correctamente']);
    }
}
