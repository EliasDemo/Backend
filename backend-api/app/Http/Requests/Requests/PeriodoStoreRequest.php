<?php

namespace App\Http\Requests\Periodo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeriodoStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'codigo'        => ['nullable','regex:/^\d{4}-(1|2)$/','unique:periodos_academicos,codigo'],
            'anio'          => ['required','integer','between:2000,2100'],
            'ciclo'         => ['required','integer', Rule::in([1,2])],
            'fecha_inicio'  => ['required','date'],
            'fecha_fin'     => ['required','date','after_or_equal:fecha_inicio'],
            'estado'        => ['nullable', Rule::in(['PLANIFICADO','EN_CURSO','CERRADO'])],
            'es_actual'     => ['boolean'],
        ];
    }
}
