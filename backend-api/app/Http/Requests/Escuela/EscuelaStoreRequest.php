<?php

namespace App\Http\Requests\Escuela;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EscuelaStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $facId = (int) $this->input('facultad_id');

        return [
            'facultad_id' => ['required','integer','exists:facultades,id'],

            'codigo' => [
                'required','string','max:32',
                Rule::unique('escuelas_profesionales','codigo')->where('facultad_id', $facId),
            ],
            'nombre' => [
                'required','string','max:150',
                Rule::unique('escuelas_profesionales','nombre')->where('facultad_id', $facId),
            ],
            'slug' => [
                'nullable','string','max:160',
                Rule::unique('escuelas_profesionales','slug')->where('facultad_id', $facId),
            ],
        ];
    }
}
