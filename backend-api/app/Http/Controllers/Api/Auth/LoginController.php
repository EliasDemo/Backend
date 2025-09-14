<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class LoginController extends Controller
{
    /**
     * Iniciar sesión y devolver token Sanctum
     */
    public function login(LoginRequest $request)
    {
        // Credenciales validadas (username/password)
        $credentials = $request->only('username', 'password');

        if (! Auth::attempt($credentials)) {
            // 401 es más semántico para auth fallida
            return response()->json([
                'message' => __('Las credenciales no son correctas.'),
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Bloqueo por suspensión (usa tu trait HasAccountStatus)
        if ($user->isSuspended()) {
            // 423 (Locked) o 403 (Forbidden). Uso 423.
            return response()->json([
                'message' => __('Tu cuenta está suspendida.'),
            ], 423);
        }

        // Crea token con nombre informativo (dispositivo/navegador)
        $tokenName = 'auth:' . Str::limit($request->userAgent() ?? 'api', 60);
        // Abilities de Sanctum: ['*'] para acceso total (ajústalo si usas scopes reales)
        $token = $user->createToken($tokenName, ['*'])->plainTextToken;

        // (Opcional) incluir roles
        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values()->all() : [];

        return response()->json([
            'message' => 'Inicio de sesión correcto',
            'user'    => new UserResource($user), // incluye profile_photo_url
            'roles'   => $roles,                  // opcional
            // 'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(), // si lo necesitas
            'token'   => $token,
        ], 200);
    }

    /**
     * Cerrar sesión y revocar tokens
     */
    public function logout(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user instanceof User) {
            // A) Si vienes con Bearer token (PAT), se puede borrar
            $current = $user->currentAccessToken(); // PersonalAccessToken|TransientToken|null

            if ($current instanceof PersonalAccessToken) {
                $current->delete(); // <-- ya no marca error
            }

            // B) Si vienes por cookie (SPA), el token es TransientToken (no se borra).
            //    En ese caso cierra la sesión web:
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
    }
}
