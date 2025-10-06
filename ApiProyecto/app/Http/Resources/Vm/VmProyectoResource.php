<?php

namespace App\Http\Resources\Vm;

use Illuminate\Http\Resources\Json\JsonResource;

class VmProyectoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'      => $this->id,
            'codigo'  => $this->codigo,
            'titulo'  => $this->titulo,
            'tipo'    => $this->tipo,
            'modalidad' => $this->modalidad,
            'estado'  => $this->estado,
            'ep_sede_id' => $this->ep_sede_id,
            'periodo_id' => $this->periodo_id,
            'horas_planificadas' => (int) $this->horas_planificadas,
            'horas_minimas_participante' => $this->horas_minimas_participante ? (int) $this->horas_minimas_participante : null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
