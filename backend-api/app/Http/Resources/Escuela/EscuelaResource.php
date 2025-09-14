<?php

namespace App\Http\Resources\Escuela;

use Illuminate\Http\Resources\Json\JsonResource;

class EscuelaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'facultad'          => [
                'id'     => $this->facultad?->id,
                'codigo' => $this->facultad?->codigo,
                'nombre' => $this->facultad?->nombre,
            ],
            'codigo'            => $this->codigo,
            'nombre'            => $this->nombre,
            'slug'              => $this->slug,
            'esta_suspendida'   => (bool) $this->esta_suspendida,
            'suspendida_desde'  => $this->suspendida_desde,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
