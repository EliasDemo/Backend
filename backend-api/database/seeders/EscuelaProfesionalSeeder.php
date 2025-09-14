<?php

namespace Database\Seeders;

use App\Models\EscuelaProfesional;
use App\Models\Facultad;
use App\Models\Universidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EscuelaProfesionalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Asegúrate de tener Universidad UPEU sembrada
            $uni = Universidad::query()
                ->where('sigla', 'UPEU')
                ->orWhere('nombre', 'UNIVERSIDAD PERUANA UNIÓN')
                ->first();

            if (! $uni) {
                throw new RuntimeException('Universidad UPEU no encontrada. Ejecuta primero UniversidadSeeder.');
            }

            // Mapa: FACULTAD(código) => lista de EPs (código, nombre)
            $escuelasPorFacultad = [
                // Facultad de Ingeniería y Arquitectura
                'FIA' => [
                    ['codigo' => 'SIS',  'nombre' => 'INGENIERÍA DE SISTEMAS'],
                    ['codigo' => 'CIV',  'nombre' => 'INGENIERÍA CIVIL'],
                    ['codigo' => 'IND',  'nombre' => 'INGENIERÍA INDUSTRIAL'],
                    ['codigo' => 'ARQ',  'nombre' => 'ARQUITECTURA'],
                ],
                // Facultad de Ciencias Empresariales
                'FCE' => [
                    ['codigo' => 'ADM',     'nombre' => 'ADMINISTRACIÓN'],
                    ['codigo' => 'CON',     'nombre' => 'CONTABILIDAD'],
                    ['codigo' => 'NEGINT',  'nombre' => 'NEGOCIOS INTERNACIONALES'],
                    ['codigo' => 'MK',      'nombre' => 'MARKETING'],
                ],
                // Facultad de Ciencias de la Salud
                'FCS' => [
                    ['codigo' => 'ENF', 'nombre' => 'ENFERMERÍA'],
                    ['codigo' => 'NUT', 'nombre' => 'NUTRICIÓN'],
                    ['codigo' => 'PSI', 'nombre' => 'PSICOLOGÍA'],
                ],
                // Facultad de Ciencias Humanas y Educación
                'FACIHED' => [
                    ['codigo' => 'EDU-PRI', 'nombre' => 'EDUCACIÓN PRIMARIA'],
                    ['codigo' => 'EDU-SEC', 'nombre' => 'EDUCACIÓN SECUNDARIA'],
                    ['codigo' => 'COM',     'nombre' => 'COMUNICACIÓN'],
                ],
                // Facultad de Teología
                'FAT' => [
                    ['codigo' => 'TEO', 'nombre' => 'TEOLOGÍA'],
                ],
            ];

            foreach ($escuelasPorFacultad as $facCodigo => $escuelas) {
                $facultad = Facultad::query()
                    ->where('universidad_id', $uni->id)
                    ->where('codigo', strtoupper(trim($facCodigo)))
                    ->first();

                if (! $facultad) {
                    throw new RuntimeException("Facultad {$facCodigo} no encontrada para la UPEU. Ejecuta FacultadSeeder antes.");
                }

                foreach ($escuelas as $ep) {
                    $codigo = strtoupper(trim($ep['codigo']));
                    $nombre = trim($ep['nombre']);
                    $slug   = Str::slug($nombre);

                    EscuelaProfesional::updateOrCreate(
                        [
                            'facultad_id' => $facultad->id,
                            'codigo'      => $codigo,
                        ],
                        [
                            'nombre'            => $nombre,
                            'slug'              => $slug,        // tu modelo lo genera si lo omites
                            'esta_suspendida'   => false,
                            'suspendida_desde'  => null,
                        ]
                    );
                }
            }
        });
    }
}
