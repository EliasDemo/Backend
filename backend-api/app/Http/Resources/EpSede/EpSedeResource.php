<?php

namespace App\Http\Resources\EpSede;

use Illuminate\Http\Resources\Json\JsonResource;

class EpSedeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->id,
            'escuela_profesional' => [
                'id'     => $this->ep?->id,
                'codigo' => $this->ep?->codigo,
                'nombre' => $this->ep?->nombre,
            ],
            'sede' => [
                'id'     => $this->sede?->id,
                'nombre' => $this->sede?->nombre,
            ],
            'vigente_desde' => $this->vigente_desde,
            'vigente_hasta' => $this->vigente_hasta,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
