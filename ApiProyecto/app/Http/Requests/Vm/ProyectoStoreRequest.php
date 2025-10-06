<?php

namespace App\Http\Requests\Vm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProyectoStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'ep_sede_id'  => ['required','integer','exists:ep_sede,id'],
            'periodo_id'  => ['required','integer','exists:periodos_academicos,id'],
            'codigo'      => ['nullable','string','max:255','unique:vm_proyectos,codigo'],
            'titulo'      => ['required','string','max:255'],
            'descripcion' => ['nullable','string'],
            'tipo'        => ['required', Rule::in(['PROYECTO','SERVICIO','INVESTIGACION','OTRO'])],
            'modalidad'   => ['required', Rule::in(['PRESENCIAL','VIRTUAL','MIXTA'])],
            'horas_planificadas'         => ['required','integer','min:1','max:32767'],
            'horas_minimas_participante' => ['nullable','integer','min:0','max:32767'],
        ];
    }
}
