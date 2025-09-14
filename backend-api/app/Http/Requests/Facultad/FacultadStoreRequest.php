<?php

namespace App\Http\Requests\Facultad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultadStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $fixed = (int) config('universidad.fixed_id', 1);

        return [
            'codigo' => [
                'required','string','max:32',
                Rule::unique('facultades','codigo')->where('universidad_id', $fixed),
            ],
            'nombre' => [
                'required','string','max:150',
                Rule::unique('facultades','nombre')->where('universidad_id', $fixed),
            ],
            'slug' => [
                'nullable','string','max:160',
                Rule::unique('facultades','slug')->where('universidad_id', $fixed),
            ],
        ];
    }
}
