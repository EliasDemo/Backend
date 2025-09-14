<?php

namespace App\Http\Requests\Estudiante;

use Illuminate\Foundation\Http\FormRequest;

class EstudianteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin (y opcional: Coordinador con scope, si lo agregas en Policy/Service)
        return $this->user()?->hasRole('Administrador') ?? false;
    }

    public function rules(): array
    {
        return [
            'dni'                 => ['nullable','string','max:12','unique:personas,dni'],
            'apellidos'           => ['required','string'],
            'nombres'             => ['required','string'],
            'email_institucional' => ['nullable','email','ends_with:upeu.edu.pe','unique:personas,email_institucional'],
            'email_personal'      => ['nullable','email'],
            'celular'             => ['nullable','string','max:20'],
            'sexo'                => ['nullable','in:M,F,X'],
            'fecha_nacimiento'    => ['nullable','date'],

            'ep_sede_id'          => ['required','integer','exists:ep_sede,id'],
            'ingreso_periodo_id'  => ['nullable','integer','exists:periodos_academicos,id'],
            'codigo'              => ['nullable','string','unique:estudiantes,codigo'],
            'ciclo_actual'        => ['nullable','integer','min:1','max:20'],
            'cohorte_codigo'      => ['nullable','string','max:20'],
        ];
    }
}
