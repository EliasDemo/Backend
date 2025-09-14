<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Administrador') ?? false;
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable','email','unique:users,email,' . $this->user()->id],
            // agrega otros campos si decides permitirlos (profile_photo, etc.)
        ];
    }
}
