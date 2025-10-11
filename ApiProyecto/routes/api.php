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

// NUEVO: Agenda (alumno/staff)
use App\Http\Controllers\Api\Vm\AgendaController;

// NUEVO: Asistencia (QR / Manual / Reportes)
use App\Http\Controllers\Api\Vm\AsistenciasController;

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

    // NUEVO: Agenda Alumno (dashboard estudiante)
    Route::get('/alumno/agenda', [AgendaController::class, 'agendaAlumno'])
        ->name('vm.alumno.agenda');

    // NUEVO: Check-in por QR (alumno escanea QR)
    Route::post('/sesiones/{sesion}/check-in/qr', [AsistenciasController::class, 'checkInPorQr'])
        ->whereNumber('sesion')
        ->name('vm.sesiones.checkin-qr');
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

    Route::get('/proyectos/{proyecto}/inscritos',  [InscripcionProyectoController::class, 'listarInscritos'])
        ->whereNumber('proyecto')
        ->name('vm.proyectos.inscritos');

    Route::get('/proyectos/{proyecto}/candidatos', [InscripcionProyectoController::class, 'listarCandidatos'])
        ->whereNumber('proyecto')
        ->name('vm.proyectos.candidatos');

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

    // ─── NUEVO: Agenda Staff (dashboard coordinador/encargado) ───
    Route::get('/staff/agenda', [AgendaController::class, 'agendaStaff'])
        ->name('vm.staff.agenda');

    // ─── NUEVO: ASISTENCIAS (staff) ───
    // Abrir ventana QR (30 min por defecto)
    Route::post('/sesiones/{sesion}/qr', [AsistenciasController::class, 'generarQr'])
        ->whereNumber('sesion')
        ->name('vm.sesiones.abrir-qr');

    // Activar ventana de llamado manual (30 min por defecto)
    Route::post('/sesiones/{sesion}/activar-manual', [AsistenciasController::class, 'activarManual'])
        ->whereNumber('sesion')
        ->name('vm.sesiones.activar-manual');

    // Check-in manual por DNI/Código
    Route::post('/sesiones/{sesion}/check-in/manual', [AsistenciasController::class, 'checkInManual'])
        ->whereNumber('sesion')
        ->name('vm.sesiones.checkin-manual');

    // Listado de asistencias por sesión
    Route::get('/sesiones/{sesion}/asistencias', [AsistenciasController::class, 'listarAsistencias'])
        ->whereNumber('sesion')
        ->name('vm.sesiones.asistencias');

    // Reporte CSV/JSON de asistencias
    Route::get('/sesiones/{sesion}/asistencias/reporte', [AsistenciasController::class, 'reporte'])
        ->whereNumber('sesion')
        ->name('vm.sesiones.asistencias.reporte');

    // Validación masiva (y opcional creación de registro_horas)
    Route::post('/sesiones/{sesion}/validar', [AsistenciasController::class, 'validarAsistencias'])
        ->whereNumber('sesion')
        ->name('vm.sesiones.validar');
});
