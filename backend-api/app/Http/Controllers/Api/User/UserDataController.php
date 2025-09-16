<?php
namespace App\Http\Controllers\Api\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class UserDataController extends Controller
{
    /**
     * Obtener los datos del usuario autenticado.
     */
    public function show(Request $request)
    {
        // Obtener el usuario autenticado
        $user = $request->user(); // Usamos $request->user() en lugar de Auth::user()

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado.'
            ], 401);
        }

        // Verificar si el usuario tiene roles asignados
        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames()->values()->all() : [];

        // Si el usuario no tiene roles, podemos devolver un mensaje o un valor predeterminado.
        if (empty($roles)) {
            return response()->json([
                'message' => 'El usuario no tiene roles asignados.'
            ], 404);
        }

        // Obtener los permisos del usuario (opcional si se necesita)
        $permissions = $user->permissions->pluck('name'); // Esto solo funciona si tienes permisos asociados

        // Obtener la foto de perfil
        $profilePhoto = $user->profile_photo ? url($user->profile_photo) : null;

        // Obtener los datos adicionales del usuario (como la relación con la persona)
        $persona = $user->persona;

        // Cargar las relaciones necesarias de manera segura (persona, estudiante, escuela profesional, facultad, sedes)
        $user->load('persona.estudiante.epSede.escuelaProfesional.facultad', 'persona.estudiante.epSede.sede');

        // Traer la escuela profesional, facultad y sede relacionada
        $escuelaProfesional = $user->persona->estudiante->epSede->escuelaProfesional ?? null;
        $facultad = $escuelaProfesional ? $escuelaProfesional->facultad : null;
        $sede = $user->persona->estudiante->epSede->sede ?? null;

        // Construir la respuesta
        return response()->json([
            'message' => 'Datos obtenidos correctamente',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'profile_photo' => $profilePhoto,
                'roles' => $roles,  // Roles del usuario
                'permissions' => $permissions,  // Permisos del usuario
                'persona' => $persona,  // Datos adicionales relacionados
                'escuela_profesional' => $escuelaProfesional ? $escuelaProfesional->nombre : null, // Nombre de la escuela profesional
                'facultad' => $facultad ? $facultad->nombre : null, // Nombre de la facultad
                'sede' => $sede ? $sede->nombre : null, // Nombre de la sede
            ]
        ], 200);
    }
}
