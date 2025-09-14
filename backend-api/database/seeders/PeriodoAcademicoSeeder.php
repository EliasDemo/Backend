<?php

namespace Database\Seeders;

use App\Models\PeriodoAcademico;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PeriodoAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        // Fechas realistas (ajusta a tu calendario oficial)
        $periodos = [
            // 2023
            ['codigo' => '2023-1', 'inicio' => '2023-03-06', 'fin' => '2023-07-28'],
            ['codigo' => '2023-2', 'inicio' => '2023-08-14', 'fin' => '2023-12-15'],

            // 2024
            ['codigo' => '2024-1', 'inicio' => '2024-03-04', 'fin' => '2024-07-26'],
            ['codigo' => '2024-2', 'inicio' => '2024-08-12', 'fin' => '2024-12-13'],

            // 2025 (HOY: 2025-09-14 -> en curso 2025-2)
            ['codigo' => '2025-1', 'inicio' => '2025-03-03', 'fin' => '2025-07-25'],
            ['codigo' => '2025-2', 'inicio' => '2025-08-11', 'fin' => '2025-12-12'],

            // 2026 (planificados)
            ['codigo' => '2026-1', 'inicio' => '2026-03-02', 'fin' => '2026-07-24'],
            ['codigo' => '2026-2', 'inicio' => '2026-08-10', 'fin' => '2026-12-11'],
        ];

        DB::transaction(function () use ($periodos) {
            $hoy = Carbon::today();
            $marcoActual = false;

            // Limpia la bandera para recalcular
            PeriodoAcademico::query()->update(['es_actual' => false]);

            foreach ($periodos as $p) {
                if (!preg_match('/^(?<anio>\d{4})\-(?<ciclo>[12])$/', $p['codigo'], $m)) {
                    throw ValidationException::withMessages([
                        'codigo' => "Código inválido: {$p['codigo']} (esperado AAAA-1|AAAA-2)",
                    ]);
                }

                $anio  = (int) $m['anio'];
                $ciclo = (int) $m['ciclo'];

                $inicio = Carbon::parse($p['inicio']);
                $fin    = Carbon::parse($p['fin']);

                if ($fin->lt($inicio)) {
                    throw ValidationException::withMessages([
                        'rango' => "Rango inválido en {$p['codigo']}: fin < inicio",
                    ]);
                }

                // Estado según fechas
                $estado = $hoy->lt($inicio) ? 'PLANIFICADO' : ($hoy->gt($fin) ? 'CERRADO' : 'EN_CURSO');
                $esActual = ($estado === 'EN_CURSO');

                /** @var \App\Models\PeriodoAcademico $row */
                $row = PeriodoAcademico::updateOrCreate(
                    ['anio' => $anio, 'ciclo' => $ciclo], // evita duplicados
                    [
                        'codigo'       => sprintf('%04d-%d', $anio, $ciclo),
                        'fecha_inicio' => $inicio->toDateString(),
                        'fecha_fin'    => $fin->toDateString(),
                        'estado'       => $estado,
                        'es_actual'    => false, // setea luego
                    ]
                );

                if ($esActual) {
                    // Marca este como actual y desmarca el resto
                    PeriodoAcademico::where('id', '!=', $row->id)->update(['es_actual' => false]);
                    $row->update(['es_actual' => true]);
                    $marcoActual = true;
                }
            }

            // Si no hubo periodo EN_CURSO hoy, marca como actual el siguiente planificado más cercano
            if (!$marcoActual) {
                $next = PeriodoAcademico::where('estado', 'PLANIFICADO')
                    ->orderBy('fecha_inicio', 'asc')
                    ->first();

                if ($next) {
                    PeriodoAcademico::where('id', '!=', $next->id)->update(['es_actual' => false]);
                    $next->update(['es_actual' => true]);
                } else {
                    // O el último cerrado más reciente
                    $last = PeriodoAcademico::where('estado', 'CERRADO')
                        ->orderBy('fecha_fin', 'desc')
                        ->first();

                    if ($last) {
                        PeriodoAcademico::where('id', '!=', $last->id)->update(['es_actual' => false]);
                        $last->update(['es_actual' => true]);
                    }
                }
            }
        });
    }
}
