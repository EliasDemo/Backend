<?php

namespace App\Http\Requests\Escuela;

use App\Models\EscuelaProfesional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EscuelaUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        /** @var EscuelaProfesional $row */
        $row = $this->route('escuela');

        // facultad efectiva: si no llega en input, usa la actual del row
        $facId = (int) ($this->input('facultad_id') ?? $row->facultad_id);

        return [
            'facultad_id' => ['sometimes','required','integer','exists:facultades,id'],

            'codigo' => [
                'sometimes','required','string','max:32',
                Rule::unique('escuelas_profesionales','codigo')
                    ->where('facultad_id', $facId)
                    ->ignore($row->id),
            ],
            'nombre' => [
                'sometimes','required','string','max:150',
                Rule::unique('escuelas_profesionales','nombre')
                    ->where('facultad_id', $facId)
                    ->ignore($row->id),
            ],
            'slug' => [
                'nullable','string','max:160',
                Rule::unique('escuelas_profesionales','slug')
                    ->where('facultad_id', $facId)
                    ->ignore($row->id),
            ],
        ];
    }
}
