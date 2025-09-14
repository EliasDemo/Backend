<?php

namespace App\Http\Requests\Sede;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SedeStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $universidadId = (int) config('universidad.fixed_id', 1);

        return [
            'nombre' => [
                'required','string','max:255',
                Rule::unique('sedes','nombre')
                    ->where(fn($q) => $q->where('universidad_id', $universidadId)),
            ],
            'es_principal' => ['sometimes','boolean'],
        ];
    }
}
