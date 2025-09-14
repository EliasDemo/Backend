<?php

namespace App\Http\Requests\Coordinador;

use Illuminate\Foundation\Http\FormRequest;

class UpsertAsignarEstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sólo Coordinadores
        return $this->user()?->hasRole('Coordinador') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('codigo')) {
            $this->merge(['codigo' => strtoupper(trim((string)$this->input('codigo')))]);
        }
        if ($this->has('email_institucional')) {
            $this->merge(['email_institucional' => strtolower(trim((string)$this->input('email_institucional')))]);
        }
        if ($this->has('email_personal')) {
            $this->merge(['email_personal' => strtolower(trim((string)$this->input('email_personal')))]);
        }
    }

    public function rules(): array
    {
        return [
            // Para localizar estudiante existente
            'codigo'         => ['nullable','string','max:50'],
            'dni'            => ['nullable','string','max:12'],
            'email_institucional' => ['nullable','email'],

            // Para crear si no existe (mínimos)
            'apellidos'      => ['required_without_all:codigo,dni,email_institucional','string'],
            'nombres'        => ['required_without_all:codigo,dni,email_institucional','string'],

            // Datos opcionales de persona
            'email_personal'   => ['nullable','email'],
            'celular'          => ['nullable','string','max:20'],
            'sexo'             => ['nullable','in:M,F,X'],
            'fecha_nacimiento' => ['nullable','date'],

            // Datos académicos opcionales (ep_sede_id NO se envía; lo pone el coordinador)
            'ingreso_periodo_id' => ['nullable','integer','exists:periodos_academicos,id'],
            'ciclo_actual'       => ['nullable','integer','min:1','max:20'],
            'cohorte_codigo'     => ['nullable','string','max:20'],
        ];
    }
}
