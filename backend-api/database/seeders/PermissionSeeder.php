<?php
// database/seeders/PermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Guard por defecto
        $guard = config('permission.defaults.guard', 'web');

        $permisos = [
            // Horas
            'horas.create','horas.update_own','horas.submit','horas.view_own','horas.delete_own',
            'horas.view_program','horas.view_campus','horas.review','horas.approve','horas.reject','horas.export',

            // Convocatorias / Actividades
            'convocatorias.manage','actividades.manage',

            // Estructura / catálogo
            'periodos.manage','sedes.manage','facultades.manage','escuelas.manage','ep_sede.manage',
            'personas.manage','coordinadores.assign','encargados.assign','users.manage','settings.manage',

            // Reportes
            'report.view_own','report.view_program','report.view_campus','report.view_global',
        ];

        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => $guard]);
        }

        $admin       = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => $guard]);
        $estudiante  = Role::firstOrCreate(['name' => 'Estudiante',   'guard_name' => $guard]);
        $coordinador = Role::firstOrCreate(['name' => 'Coordinador',  'guard_name' => $guard]);
        $encargado   = Role::firstOrCreate(['name' => 'Encargado',    'guard_name' => $guard]);


        $estudiante->syncPermissions([
            'horas.create','horas.update_own','horas.submit','horas.view_own','horas.delete_own',
            'report.view_own',
        ]);

        $coordinador->syncPermissions([
            'horas.view_program','horas.review','horas.approve','horas.reject','horas.export',
            'convocatorias.manage','actividades.manage',
            'report.view_program',
        ]);

        $encargado->syncPermissions([
            'horas.view_campus','horas.review','horas.export',
            'report.view_campus',
        ]);

        // Admin todo (manualmente) — o usa Gate::before
        $admin->syncPermissions(Permission::pluck('name')->all());
    }
}
