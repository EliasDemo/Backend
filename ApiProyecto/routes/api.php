<?php

use App\Http\Controllers\Api\Academico\EpSedeController;
use App\Http\Controllers\Api\Academico\EscuelaProfesionalController;
use App\Http\Controllers\Api\Academico\ExpedienteController;
use App\Http\Controllers\Api\Academico\FacultadController;
use App\Http\Controllers\Api\Academico\ResponsableEpController;
use App\Http\Controllers\Api\Academico\SedeController;
use App\Http\Controllers\Api\Login\AuthController;
use App\Http\Controllers\Api\Universidad\UniversidadController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\Vm\EventoController;
use App\Http\Controllers\Api\Vm\EventoSesionController;
use App\Http\Controllers\Api\Vm\ProcesoSesionController;
use App\Http\Controllers\Api\Vm\ProyectoController;
use App\Http\Controllers\Api\Vm\ProyectoProcesoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('/lookup', [AuthController::class, 'lookup']); // paso 1
    Route::post('/login',  [AuthController::class, 'login']);  // paso 2

    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});
Route::middleware(['auth:sanctum'])->prefix('users')->group(function () {
    Route::get('/me', [UserController::class, 'me']);                           // Perfil del autenticado
    Route::get('/by-username/{username}', [UserController::class, 'showByUsername']); // Perfil completo por username
});
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/universidad', [UniversidadController::class, 'show']);

    // si usas permisos:
    // Route::middleware('permission:editar-universidad,web')->group(function () { ... });

    Route::put('/universidad', [UniversidadController::class, 'update']);      // editar datos
    Route::post('/universidad/logo', [UniversidadController::class, 'setLogo']);       // subir logo
    Route::post('/universidad/portada', [UniversidadController::class, 'setPortada']); // subir portada
});

Route::middleware(['auth:sanctum'])->prefix('academico')->group(function () {
    // Si usas Spatie: añade ->middleware('permission:gestionar-academico,web')

    // SEDES (una universidad)
    Route::post('/sedes', [SedeController::class, 'store']);

    // FACULTADES (una universidad)
    Route::post('/facultades', [FacultadController::class, 'store']);

    // ESCUELAS PROFESIONALES
    Route::post('/escuelas', [EscuelaProfesionalController::class, 'store']);

    // EP_SEDE (vincular/desvincular)
    Route::post('/ep-sede', [EpSedeController::class, 'store']);
    Route::delete('/ep-sede/{id}', [EpSedeController::class, 'destroy']);
});
Route::middleware(['auth:sanctum'])->prefix('academico')->group(function () {
    // Asignar estudiante (o actualizar su expediente)
    Route::post('/expedientes', [ExpedienteController::class, 'store']);

    // Asignar responsables por EP_SEDE (coordinador / encargado)
    Route::post('/ep-sede/{epSede}/coordinador', [ResponsableEpController::class, 'setCoordinador']);
    Route::post('/ep-sede/{epSede}/encargado',   [ResponsableEpController::class, 'setEncargado']);
});

Route::middleware(['auth:sanctum','role:coordinador|encargado,web'])->prefix('vm')->group(function () {
    // PROYECTOS (EP_SEDE)
    Route::post('/proyectos', [ProyectoController::class, 'store']);
    Route::put('/proyectos/{proyecto}/publicar', [ProyectoController::class, 'publicar']);

    // PROCESOS del Proyecto
    Route::post('/proyectos/{proyecto}/procesos', [ProyectoProcesoController::class, 'store']);

    // SESIONES en lote
    Route::post('/procesos/{proceso}/sesiones/batch', [ProcesoSesionController::class, 'storeBatch']); // para PROCESO (Proyecto)
    Route::post('/eventos/{evento}/sesiones/batch',   [EventoSesionController::class, 'storeBatch']);   // para EVENTO (multi-día)

    // EVENTOS (Sede | Facultad | EP_SEDE)
    Route::post('/eventos', [EventoController::class, 'store']);
});
