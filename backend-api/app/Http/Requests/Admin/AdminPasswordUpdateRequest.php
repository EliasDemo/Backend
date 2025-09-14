<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminPasswordUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Administrador') ?? false;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required','string'],
            'password'         => ['required','string','min:8','confirmed'],
        ];
    }
}
