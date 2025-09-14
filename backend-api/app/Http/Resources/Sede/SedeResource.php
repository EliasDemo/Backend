<?php

namespace App\Http\Resources\Sede;

use Illuminate\Http\Resources\Json\JsonResource;

class SedeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'universidad_id'    => $this->universidad_id,
            'nombre'            => $this->nombre,
            'es_principal'      => (bool) $this->es_principal,
            'esta_suspendida'   => (bool) $this->esta_suspendida,
            'suspendida_desde'  => $this->suspendida_desde,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
