<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminPasswordUpdateRequest;
use App\Http\Requests\Admin\AdminProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminProfileController extends Controller
{
    public function update(AdminProfileUpdateRequest $request)
    {
        $user = $request->user();
        $user->fill($request->validated())->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user'    => $user->fresh(),
        ]);
    }

    public function updatePassword(AdminPasswordUpdateRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return response()->json(['message' => 'La contraseña actual no es válida'], 422);
        }

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
        ])->save();

        return response()->json(['message' => 'Contraseña actualizada correctamente']);
    }
}
