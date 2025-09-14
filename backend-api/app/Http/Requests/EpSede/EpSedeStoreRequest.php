<?php

namespace App\Http\Requests\EpSede;

use App\Rules\EpSede\NoOverlapEpSede;
use Illuminate\Foundation\Http\FormRequest;

class EpSedeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Toma los valores del input para pasarlos a la Rule
        $epId   = (int) $this->input('escuela_profesional_id');
        $sedeId = (int) $this->input('sede_id');
        $desde  = $this->input('vigente_desde');  // puede ser null
        $hasta  = $this->input('vigente_hasta');  // puede ser null

        return [
            'escuela_profesional_id' => ['required','integer','exists:escuelas_profesionales,id'],
            'sede_id'                => ['required','integer','exists:sedes,id'],

            // Un único bloque de reglas para 'vigente_desde' (incluye tu NoOverlapEpSede)
            'vigente_desde'          => ['nullable','date', new NoOverlapEpSede($epId, $sedeId, $desde, $hasta)],

            'vigente_hasta'          => ['nullable','date','after_or_equal:vigente_desde'],
        ];
    }
}
