<?php

namespace App\Http\Controllers\Api\RolePermission;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RolePermissionController extends Controller
{
    // Mostrar roles disponibles junto con sus permisos
    public function index()
    {
        // Obtener roles con sus permisos asociados
        $roles = Role::with('permissions')->get();

        // Respuesta en formato JSON
        return response()->json([
            'data' => $roles
        ], 200);
    }

    // Crear un nuevo rol
    public function createRole(Request $request)
    {
        // Validación de los datos de entrada
        $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        // Crear el rol
        $role = Role::create(['name' => $request->name]);

        // Respuesta en formato JSON
        return response()->json([
            'message' => 'Role created successfully.',
            'data' => $role
        ], 201);
    }

    // Asignar un rol a un usuario
    public function assignRole(Request $request, $userId)
    {
        // Validación de los datos de entrada
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        // Buscar el usuario
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        // Asignar el rol
        $user->assignRole($request->role);

        // Respuesta en formato JSON
        return response()->json([
            'message' => 'Role assigned successfully.',
            'data' => $user
        ], 200);
    }

    // Crear un permiso
    public function createPermission(Request $request)
    {
        // Validación de los datos de entrada
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        // Crear el permiso
        $permission = Permission::create(['name' => $request->name]);

        // Respuesta en formato JSON
        return response()->json([
            'message' => 'Permission created successfully.',
            'data' => $permission
        ], 201);
    }

    // Asignar un permiso a un rol
    public function assignPermissionToRole(Request $request, $roleId)
    {
        // Validación de los datos de entrada
        $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);

        // Buscar el rol
        $role = Role::findById($roleId);
        if (!$role) {
            return response()->json([
                'message' => 'Role not found.'
            ], 404);
        }

        // Asignar el permiso al rol
        $role->givePermissionTo($request->permission);

        // Respuesta en formato JSON
        return response()->json([
            'message' => 'Permission assigned to role successfully.',
            'data' => $role
        ], 200);
    }
}
