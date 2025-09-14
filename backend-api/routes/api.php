<?php

use App\Http\Controllers\Api\Admin\AdminProfileController;
use App\Http\Controllers\Api\Registration\CoordinadorRegistrationController;
use App\Http\Controllers\Api\Registration\EncargadoRegistrationController;
use App\Http\Controllers\Api\Registration\EstudianteRegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\EpSede\EpSedeController;
use App\Http\Controllers\Api\Escuela\EscuelaController;
use App\Http\Controllers\Api\Facultad\FacultadController;
use App\Http\Controllers\Api\Periodo\PeriodoController;
use App\Http\Controllers\Api\Sede\SedeController;
use App\Http\Controllers\Api\Coordinador\AsignacionEpController;
use App\Http\Controllers\Api\Coordinador\EstudiantesAutoController;

/*
|--------------------------------------------------------------------------
| Rutas públicas (no autenticadas)
|--------------------------------------------------------------------------
*/
Route::post('/login', [LoginController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Rutas autenticadas (token Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        /** @var \App\Models\User $user */
        return $request->user();
    });

    // Logout
    Route::post('/logout', [LoginController::class, 'logout']);


    /*
    |--------------------------------------------------------------------------
    | Lectura general (acceso para cualquier autenticado)
    | - Si quieres, puedes afinar con policies para filtrar por EP–Sede/Sede.
    |--------------------------------------------------------------------------
    */

    // Periodos (consultas)
    Route::get('/periodos',        [PeriodoController::class, 'index']);
    Route::get('/periodos/actual', [PeriodoController::class, 'actual']);

    // Consulta de estructuras (lectura)
    Route::get('/ep-sede',   [EpSedeController::class, 'index']);      // listar ofertas EP–Sede (lectura)
    Route::get('/sedes',     [SedeController::class, 'index']);        // listar sedes (lectura)
    Route::get('/facultades',[FacultadController::class, 'index']);    // listar facultades (lectura)
    Route::get('/escuelas',  [EscuelaController::class, 'index']);     // listar escuelas (lectura)


    /*
    |--------------------------------------------------------------------------
    | Administración estricta (solo Administrador)
    | - Requiere rol: Administrador
    | - Además se refuerza con permisos específicos (definidos en tu PermissionSeeder)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:Administrador'])->group(function () {

        // Sedes (gestión)
        Route::post('/sedes', [SedeController::class, 'store'])
            ->middleware('permission:sedes.manage');
        Route::match(['put','patch'], '/sedes/{sede}', [SedeController::class, 'update'])
            ->middleware('permission:sedes.manage');

        Route::patch('/sedes/{sede}/principal', [SedeController::class, 'makePrincipal'])
            ->middleware('permission:sedes.manage');
        Route::patch('/sedes/{sede}/suspender', [SedeController::class, 'suspend'])
            ->middleware('permission:sedes.manage');
        Route::patch('/sedes/{sede}/restaurar', [SedeController::class, 'restore'])
            ->middleware('permission:sedes.manage');

        // Facultades (gestión)
        Route::post('/facultades', [FacultadController::class, 'store'])
            ->middleware('permission:facultades.manage');
        Route::patch('/facultades/{facultad}', [FacultadController::class, 'update'])
            ->middleware('permission:facultades.manage');
        Route::patch('/facultades/{facultad}/suspender', [FacultadController::class, 'suspender'])
            ->middleware('permission:facultades.manage');
        Route::patch('/facultades/{facultad}/restaurar', [FacultadController::class, 'restaurar'])
            ->middleware('permission:facultades.manage');

        // Escuelas (gestión)
        Route::post('/escuelas', [EscuelaController::class, 'store'])
            ->middleware('permission:escuelas.manage');
        Route::patch('/escuelas/{escuela}', [EscuelaController::class, 'update'])
            ->middleware('permission:escuelas.manage');
        Route::patch('/escuelas/{escuela}/suspender', [EscuelaController::class, 'suspender'])
            ->middleware('permission:escuelas.manage');
        Route::patch('/escuelas/{escuela}/restaurar', [EscuelaController::class, 'restaurar'])
            ->middleware('permission:escuelas.manage');

        // EP–Sede (gestión)
        Route::post('/ep-sede', [EpSedeController::class, 'store'])
            ->middleware('permission:ep_sede.manage');
        Route::patch('/ep-sede/{ep_sede}', [EpSedeController::class, 'update'])
            ->middleware('permission:ep_sede.manage');
        Route::patch('/ep-sede/{ep_sede}/cerrar', [EpSedeController::class, 'close'])
            ->middleware('permission:ep_sede.manage');

        // Periodos (gestión)
        Route::post('/periodos', [PeriodoController::class, 'store'])
            ->middleware('permission:periodos.manage');
        Route::patch('/periodos/{periodo}', [PeriodoController::class, 'update'])
            ->middleware('permission:periodos.manage');
        Route::patch('/periodos/{periodo}/marcar-actual', [PeriodoController::class, 'marcarActual'])
            ->middleware('permission:periodos.manage');
    });


    Route::middleware(['auth:sanctum','role:Administrador'])->group(function () {
    // Admin
        Route::patch('/admin/me', [AdminProfileController::class, 'update']);
        Route::patch('/admin/password', [AdminProfileController::class, 'updatePassword']);

        // Registro de roles operativos
        Route::post('/register/coordinador', [CoordinadorRegistrationController::class, 'store']);
        Route::post('/register/encargado',   [EncargadoRegistrationController::class, 'store']);
        Route::post('/register/estudiante',  [EstudianteRegistrationController::class, 'store']);
    });

    Route::middleware(['auth:sanctum','role:Coordinador'])->group(function () {
        Route::post('/coordinador/estudiantes/asignar-ep', [AsignacionEpController::class, 'assign']);
        Route::post('/coordinador/estudiantes/upsert-asignar', [EstudiantesAutoController::class, 'upsertAndAssign']);

    });


    /*
    |--------------------------------------------------------------------------
    | Scopes operativos (Coordinador / Encargado) — ejemplos
    | - Aquí pondrás endpoints de revisión/aprobación/export propios del módulo de horas.
    | - De momento, los dejamos ilustrativos para cuando agregues esos controladores.
    |--------------------------------------------------------------------------
    */

    // Coordinador (EP–Sede del periodo actual)
    Route::middleware(['role:Coordinador|Administrador'])->group(function () {
        // Ejemplos:
        // Route::get('/programa/horas', [HorasController::class, 'indexPrograma'])
        //     ->middleware('permission:horas.view_program');
        // Route::patch('/horas/{id}/aprobar', [HorasController::class,'aprobar'])
        //     ->middleware('permission:horas.approve');
        // Route::patch('/horas/{id}/rechazar', [HorasController::class,'rechazar'])
        //     ->middleware('permission:horas.reject');
        // Route::get('/programa/horas/export', [HorasController::class,'export'])
        //     ->middleware('permission:horas.export');
    });

    // Encargado (Sede del periodo actual)
    Route::middleware(['role:Encargado|Administrador'])->group(function () {
        // Ejemplos:
        // Route::get('/sede/horas', [HorasController::class, 'indexSede'])
        //     ->middleware('permission:horas.view_campus');
        // Route::get('/sede/horas/export', [HorasController::class,'exportSede'])
        //     ->middleware('permission:horas.export');
    });

    // Estudiante (acceso limitado a sus propias horas)
    Route::middleware(['role:Estudiante|Administrador'])->group(function () {
        // Ejemplos:
        // Route::get('/mis-horas', [HorasController::class, 'indexOwn'])
        //     ->middleware('permission:horas.view_own');
        // Route::post('/horas', [HorasController::class, 'store'])
        //     ->middleware('permission:horas.create');
        // Route::patch('/horas/{id}', [HorasController::class, 'update'])
        //     ->middleware('permission:horas.update_own');
        // Route::post('/horas/{id}/submit', [HorasController::class, 'submit'])
        //     ->middleware('permission:horas.submit');
    });
});
