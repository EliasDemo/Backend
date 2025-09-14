<?php

namespace App\Http\Resources\Facultad;

use Illuminate\Http\Resources\Json\JsonResource;

class FacultadResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'universidad_id'    => $this->universidad_id,
            'codigo'            => $this->codigo,
            'nombre'            => $this->nombre,
            'slug'              => $this->slug,
            'esta_suspendida'   => (bool) $this->esta_suspendida,
            'suspendida_desde'  => $this->suspendida_desde,
            'escuelas_count'    => $this->when(isset($this->escuelas_count), $this->escuelas_count),
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
