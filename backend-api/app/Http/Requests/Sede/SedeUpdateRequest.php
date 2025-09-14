<?php

namespace App\Http\Requests\Sede;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SedeUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $universidadId = (int) config('universidad.fixed_id', 1);
        $sedeId = $this->route('sede')?->id;

        return [
            'nombre' => [
                'sometimes','required','string','max:255',
                Rule::unique('sedes','nombre')
                    ->where(fn($q) => $q->where('universidad_id', $universidadId))
                    ->ignore($sedeId),
            ],
            'es_principal' => ['sometimes','boolean'],
        ];
    }
}
