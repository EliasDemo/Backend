<?php

namespace App\Http\Requests\Encargado;

use Illuminate\Foundation\Http\FormRequest;

class EncargadoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ya protegemos por middleware 'permission', aquí puede ser true
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cargo'          => $this->input('cargo', 'ENCARGADO DE SEDE'),
            'activo'         => $this->boolean('activo'),
            'crear_usuario'  => $this->has('crear_usuario') ? $this->boolean('crear_usuario') : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'sede_id'        => ['required','integer','exists:sedes,id'],
            'periodo_id'     => ['nullable','integer','exists:periodos_academicos,id'],

            'user_id'        => ['nullable','integer','exists:users,id'],
            'persona_id'     => ['nullable','integer','exists:personas,id'],
            'nombres'             => ['required_without_all:user_id,persona_id','string','max:150'],
            'apellidos'           => ['required_without_all:user_id,persona_id','string','max:150'],
            'dni'                 => ['nullable','string','max:12'],
            'email_institucional' => ['nullable','email','max:150'],
            'email_personal'      => ['nullable','email','max:150'],
            'celular'             => ['nullable','string','max:20'],

            'crear_usuario'  => ['boolean'],
            'username'       => ['nullable','string','min:3','max:50'],
            'password'       => ['nullable','string','min:8','max:100'],

            'cargo'          => ['nullable','string','max:80'],
            'activo'         => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombres.required_without_all'   => 'Nombres requeridos si no especificas user_id ni persona_id.',
            'apellidos.required_without_all' => 'Apellidos requeridos si no especificas user_id ni persona_id.',
        ];
    }
}
