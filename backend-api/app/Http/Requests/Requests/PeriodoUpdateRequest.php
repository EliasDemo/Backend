<?php

namespace App\Http\Requests\Periodo;

use App\Models\PeriodoAcademico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeriodoUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        /** @var PeriodoAcademico $row */
        $row = $this->route('periodo');

        return [
            'codigo'        => ['nullable','regex:/^\d{4}-(1|2)$/', Rule::unique('periodos_academicos','codigo')->ignore($row->id)],
            'anio'          => ['sometimes','required','integer','between:2000,2100'],
            'ciclo'         => ['sometimes','required','integer', Rule::in([1,2])],
            'fecha_inicio'  => ['sometimes','required','date'],
            'fecha_fin'     => ['sometimes','required','date','after_or_equal:fecha_inicio'],
            'estado'        => ['nullable', Rule::in(['PLANIFICADO','EN_CURSO','CERRADO'])],
            'es_actual'     => ['boolean'],
        ];
    }
}
