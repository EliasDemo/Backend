<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\Universidad;
use App\Models\Facultad;
use App\Models\EscuelaProfesional;
use App\Models\EpSede;
use App\Models\PeriodoAcademico;
use App\Models\CoordinadorEpSede; // Modelo para tabla coordinadores_ep_sede
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EncargadoEpSedeDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $guard = config('permission.defaults.guard', 'web');

            // ========= 0) Parámetros por .env (con defaults) =========
            $sedeNombre   = env('SEED_ENC_SEDE', 'Lima'); // Lima | Juliaca | Tarapoto
            $facCodigo    = strtoupper(trim(env('SEED_ENC_FAC', 'FIA')));
            $epCodigo     = strtoupper(trim(env('SEED_ENC_EP',  'SIS'))); // p.ej. SIS
            $username     = env('SEED_ENC_USERNAME', 'encargado.ep');
            $plainPass    = env('SEED_ENC_PASSWORD', 'Password123!');
            $dniDemo      = env('SEED_ENC_DNI', '71234568');
            $cargo        = env('SEED_ENC_CARGO', 'ENCARGADO EP');

            // ========= 1) Universidad (UPEU) =========
            $uni = Universidad::query()
                ->where('sigla', 'UPEU')
                ->orWhere('nombre', 'UNIVERSIDAD PERUANA UNIÓN')
                ->first();

            if (! $uni) {
                throw new RuntimeException('Universidad UPEU no encontrada. Ejecuta primero UniversidadSeeder.');
            }

            // ========= 2) Sede =========
            $sede = Sede::query()
                ->where('universidad_id', $uni->id)
                ->where('nombre', $sedeNombre)
                ->first();

            if (! $sede) {
                throw new RuntimeException("Sede '{$sedeNombre}' no encontrada. Ejecuta SedeSeeder primero.");
            }

            // ========= 3) Facultad y EP =========
            $fac = Facultad::query()
                ->where('universidad_id', $uni->id)
                ->where('codigo', $facCodigo)
                ->first();

            if (! $fac) {
                throw new RuntimeException("Facultad '{$facCodigo}' no encontrada. Ejecuta FacultadSeeder primero.");
            }

            $ep = EscuelaProfesional::query()
                ->where('facultad_id', $fac->id)
                ->where('codigo', $epCodigo)
                ->first();

            if (! $ep) {
                throw new RuntimeException("EP código '{$epCodigo}' no encontrada en facultad {$facCodigo}. Ejecuta EscuelaProfesionalSeeder primero.");
            }

            // ========= 4) EP–Sede vigente (o el más reciente) =========
            $hoy = now()->toDateString();

            $epSede = EpSede::query()
                ->where('escuela_profesional_id', $ep->id)
                ->where('sede_id', $sede->id)
                ->where(function ($q) use ($hoy) {
                    $q->whereNull('vigente_desde')->orWhere('vigente_desde', '<=', $hoy);
                })
                ->where(function ($q) use ($hoy) {
                    $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $hoy);
                })
                ->orderByDesc('vigente_desde')
                ->first();

            // Si no hay vigente, toma el último registro cargado para esa pareja EP–Sede
            if (! $epSede) {
                $epSede = EpSede::query()
                    ->where('escuela_profesional_id', $ep->id)
                    ->where('sede_id', $sede->id)
                    ->orderByDesc('vigente_desde')
                    ->first();
            }

            if (! $epSede) {
                throw new RuntimeException("No existe oferta EP–Sede para {$epCodigo} en {$sedeNombre}. Ejecuta EpSedeSeeder primero.");
            }

            // ========= 5) Periodo actual =========
            $periodo = PeriodoAcademico::where('es_actual', true)->first();
            if (! $periodo) {
                $anio  = (int) now()->format('Y');
                $ciclo = (now()->format('n') <= 7) ? 1 : 2;
                $periodo = PeriodoAcademico::firstOrCreate(
                    ['codigo' => "{$anio}-{$ciclo}"],
                    [
                        'anio'       => $anio,
                        'ciclo'      => $ciclo,
                        'estado'     => 'EN_CURSO',
                        'es_actual'  => true,
                        'fecha_inicio' => now()->startOfMonth()->toDateString(),
                        'fecha_fin'    => now()->endOfMonth()->toDateString(),
                    ]
                );
            }

            // ========= 6) Persona + User =========
            $persona = Persona::firstOrCreate(
                ['dni' => $dniDemo],
                [
                    'apellidos'           => 'Encargado EP Demo',
                    'nombres'             => 'UPeU',
                    'email_institucional' => 'encargado.ep@upeu.edu.pe',
                    'celular'             => '999777666',
                ]
            );

            $user = User::firstOrCreate(
                ['username' => $username],
                [
                    'email'      => $persona->email_institucional,
                    'password'   => Hash::make($plainPass),
                    'persona_id' => $persona->id,
                    'status'     => 'active',
                ]
            );

            // ========= 7) Rol Encargado =========
            $role = Role::firstOrCreate(['name' => 'Encargado', 'guard_name' => $guard]);
            if (! $user->hasRole($role->name)) {
                $user->assignRole($role->name);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // ========= 8) Designación por EP–Sede y periodo =========
            // Usamos tu tabla 'coordinadores_ep_sede' por ser la que modela cargos a nivel EP–Sede
            $designacion = CoordinadorEpSede::updateOrCreate(
                ['ep_sede_id' => $epSede->id, 'periodo_id' => $periodo->id],
                [
                    'persona_id' => $persona->id,
                    'cargo'      => $cargo,    // 'ENCARGADO EP'
                    'activo'     => true,
                ]
            );

            // ========= 9) Consola =========
            $this->command?->info("✅ Encargado EP creado/actualizado:");
            $this->command?->line("   Universidad: {$uni->sigla} | Sede: {$sedeNombre}");
            $this->command?->line("   Facultad: {$facCodigo} | EP: {$epCodigo} | ep_sede_id: {$epSede->id}");
            $this->command?->line("   Periodo: {$periodo->codigo} | Designación ID: {$designacion->id}");
            $this->command?->line("   Usuario: {$user->username} | Password: {$plainPass} (si ya existía, ignora)");
        });
    }
}
