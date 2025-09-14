<?php

namespace App\Http\Resources\Periodo;

use Illuminate\Http\Resources\Json\JsonResource;

class PeriodoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'codigo'         => $this->codigo,
            'anio'           => $this->anio,
            'ciclo'          => $this->ciclo,
            'estado'         => $this->estado,
            'es_actual'      => (bool) $this->es_actual,
            'fecha_inicio'   => $this->fecha_inicio,
            'fecha_fin'      => $this->fecha_fin,
            'duracion_dias'  => $this->duracion_dias ?? null,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
