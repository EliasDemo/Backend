<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Login\AuthController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\Lookup\LookupController;
use App\Http\Controllers\Api\Universidad\UniversidadController;

use App\Http\Controllers\Api\Academico\SedeController;
use App\Http\Controllers\Api\Academico\FacultadController;
use App\Http\Controllers\Api\Academico\EscuelaProfesionalController;
use App\Http\Controllers\Api\Academico\EpSedeController;
use App\Http\Controllers\Api\Academico\ResponsableEpController;
use App\Http\Controllers\Api\Academico\ExpedienteController;

use App\Http\Controllers\Api\Vm\ProyectoController;
use App\Http\Controllers\Api\Vm\ProyectoProcesoController;
use App\Http\Controllers\Api\Vm\ProcesoSesionController;
use App\Http\Controllers\Api\Vm\EventoController;
use App\Http\Controllers\Api\Vm\EventoSesionController;
use App\Http\Controllers\Api\Vm\EditarProyectoController;
use App\Http\Controllers\Api\Vm\InscripcionProyectoController;
use App\Http\Controllers\Api\Vm\ProyectoImagenController;

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación básica / perfil
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->get('/user', fn (Request $r) => $r->user());

Route::prefix('auth')->group(function () {
    Route::post('/lookup', [AuthController::class, 'lookup']);
    Route::post('/login',  [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum'])->prefix('users')->group(function () {
    Route::get('/me', [UserController::class, 'me']);
    Route::get('/by-username/{username}', [UserController::class, 'showByUsername']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Universidad
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/universidad', [UniversidadController::class, 'show']);
    Route::put('/universidad', [UniversidadController::class, 'update']);
    Route::post('/universidad/logo', [UniversidadController::class, 'setLogo']);
    Route::post('/universidad/portada', [UniversidadController::class, 'setPortada']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Académico
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->prefix('academico')->group(function () {
    Route::post('/sedes', [SedeController::class, 'store']);
    Route::post('/facultades', [FacultadController::class, 'store']);
    Route::post('/escuelas', [EscuelaProfesionalController::class, 'store']);
    Route::post('/ep-sede', [EpSedeController::class, 'store']);
    Route::delete('/ep-sede/{id}', [EpSedeController::class, 'destroy']);

    Route::post('/expedientes', [ExpedienteController::class, 'store']);
    Route::post('/ep-sede/{epSede}/coordinador', [ResponsableEpController::class, 'setCoordinador']);
    Route::post('/ep-sede/{epSede}/encargado',   [ResponsableEpController::class, 'setEncargado']);
});

// ─────────────────────────────────────────────────────────────────────────────
// Lookups
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->prefix('lookups')->group(function () {
    Route::get('/ep-sedes',  [LookupController::class, 'epSedes']);   // ?q=...&limit=...
    Route::get('/periodos',  [LookupController::class, 'periodos']);  // ?q=...&solo_activos=1
});

// ─────────────────────────────────────────────────────────────────────────────
// VM (Proyectos/Procesos/Sesiones/Eventos)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * 1) RUTAS PARA ALUMNO  (van PRIMERO y con constraints)
 */
Route::middleware(['auth:sanctum'])->prefix('vm')->group(function () {
    // Listado para el estudiante
    Route::get('/proyectos/alumno', [ProyectoController::class, 'indexAlumno'])
        ->name('vm.proyectos.index-alumno');

    // Inscripción a proyecto
    Route::post('/proyectos/{proyecto}/inscribirse', [InscripcionProyectoController::class, 'inscribirProyecto'])
        ->whereNumber('proyecto')
        ->name('vm.proyectos.inscribirse');
});

/**
 * 2) RUTAS DE GESTIÓN (COORDINADOR / ENCARGADO)
 */
Route::middleware(['auth:sanctum','role:COORDINADOR|ENCARGADO'])->prefix('vm')->group(function () {

    // Específica primero
    Route::get('/proyectos/niveles-disponibles', [ProyectoController::class, 'nivelesDisponibles'])
        ->name('vm.proyectos.niveles-disponibles');

    // Proyectos
    Route::get   ('/proyectos',                 [ProyectoController::class, 'index']);
    Route::post  ('/proyectos',                 [ProyectoController::class, 'store']);
    Route::get   ('/proyectos/{proyecto}',      [EditarProyectoController::class, 'show'])->whereNumber('proyecto');
    Route::get   ('/proyectos/{proyecto}/edit', [EditarProyectoController::class, 'show'])->whereNumber('proyecto');
    Route::put   ('/proyectos/{proyecto}',      [EditarProyectoController::class, 'update'])->whereNumber('proyecto');
    Route::delete('/proyectos/{proyecto}',      [EditarProyectoController::class, 'destroy'])->whereNumber('proyecto');
    Route::put   ('/proyectos/{proyecto}/publicar', [ProyectoController::class, 'publicar'])->whereNumber('proyecto');

    // Imágenes
    Route::get   ('/proyectos/{proyecto}/imagenes',          [ProyectoImagenController::class, 'index'])->whereNumber('proyecto');
    Route::post  ('/proyectos/{proyecto}/imagenes',          [ProyectoImagenController::class, 'store'])->whereNumber('proyecto');
    Route::delete('/proyectos/{proyecto}/imagenes/{imagen}', [ProyectoImagenController::class, 'destroy'])
        ->whereNumber('proyecto')->whereNumber('imagen');

    // Procesos
    Route::post  ('/proyectos/{proyecto}/procesos', [ProyectoProcesoController::class, 'store'])->whereNumber('proyecto');

    // Sesiones
    Route::post('/procesos/{proceso}/sesiones/batch', [ProcesoSesionController::class, 'storeBatch'])->whereNumber('proceso');

    // Eventos
    Route::post('/eventos', [EventoController::class, 'store']);
    Route::post('/eventos/{evento}/sesiones/batch', [EventoSesionController::class, 'storeBatch'])->whereNumber('evento');
});


