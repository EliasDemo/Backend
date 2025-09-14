<?php

namespace App\Http\Requests\Coordinador;

use Illuminate\Foundation\Http\FormRequest;

class AsignarEstudianteAEpRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo coordinadores pueden usarlo
        return $this->user()?->hasRole('Coordinador') ?? false;
    }

    public function rules(): array
    {
        return [
            // Debe venir al menos uno
            'estudiante_id' => ['nullable','integer','exists:estudiantes,id','required_without:codigo'],
            'codigo'        => ['nullable','string','exists:estudiantes,codigo','required_without:estudiante_id'],
        ];
    }
}
