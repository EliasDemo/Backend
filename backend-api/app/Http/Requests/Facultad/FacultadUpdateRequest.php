<?php

namespace App\Http\Requests\Facultad;

use App\Models\Facultad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultadUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        /** @var Facultad $row */
        $row   = $this->route('facultad');
        $fixed = (int) config('universidad.fixed_id', 1);

        return [
            'codigo' => [
                'sometimes','required','string','max:32',
                Rule::unique('facultades','codigo')
                    ->where('universidad_id', $fixed)
                    ->ignore($row->id),
            ],
            'nombre' => [
                'sometimes','required','string','max:150',
                Rule::unique('facultades','nombre')
                    ->where('universidad_id', $fixed)
                    ->ignore($row->id),
            ],
            'slug' => [
                'nullable','string','max:160',
                Rule::unique('facultades','slug')
                    ->where('universidad_id', $fixed)
                    ->ignore($row->id),
            ],
        ];
    }
}
