<?php

namespace App\Http\Requests\Encargado;

use Illuminate\Foundation\Http\FormRequest;

class EncargadoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
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

            'sede_id'             => ['required','integer','exists:sedes,id'],
        ];
    }
}
