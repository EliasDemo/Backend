<?php

namespace App\Http\Requests\EpSede;

use App\Models\EpSede;
use App\Rules\EpSede\NoOverlapEpSede;
use Illuminate\Foundation\Http\FormRequest;

class EpSedeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var EpSede|null $row */
        $row = $this->route('ep_sede'); // Route Model Binding

        // IDs efectivos (prioriza input, si no usa lo del row actual)
        $epId   = (int) ($this->input('escuela_profesional_id') ?? $row?->escuela_profesional_id);
        $sedeId = (int) ($this->input('sede_id')                ?? $row?->sede_id);

        // Fechas efectivas como string 'Y-m-d' (prioriza input)
        $desde = $this->input('vigente_desde');
        if ($desde === null && $row && $row->vigente_desde instanceof \DateTimeInterface) {
            $desde = $row->vigente_desde->format('Y-m-d');
        }

        $hasta = $this->input('vigente_hasta');
        if ($hasta === null && $row && $row->vigente_hasta instanceof \DateTimeInterface) {
            $hasta = $row->vigente_hasta->format('Y-m-d');
        }

        return [
            'escuela_profesional_id' => ['sometimes','required','integer','exists:escuelas_profesionales,id'],
            'sede_id'                => ['sometimes','required','integer','exists:sedes,id'],

            // Un solo bloque de reglas para 'vigente_desde'
            'vigente_desde'          => ['nullable','date', new NoOverlapEpSede($epId, $sedeId, $desde, $hasta, $row?->id)],
            'vigente_hasta'          => ['nullable','date','after_or_equal:vigente_desde'],
        ];
    }
}
