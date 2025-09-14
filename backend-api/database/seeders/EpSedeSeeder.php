<?php

namespace Database\Seeders;

use App\Models\EpSede;
use App\Models\Facultad;
use App\Models\EscuelaProfesional;
use App\Models\Sede;
use App\Models\Universidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class EpSedeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1) Universidad objetivo (UPEU)
            $uni = Universidad::query()
                ->where('sigla', 'UPEU')
                ->orWhere('nombre', 'UNIVERSIDAD PERUANA UNIÓN')
                ->first();

            if (! $uni) {
                throw new RuntimeException('Universidad UPEU no encontrada. Ejecuta primero UniversidadSeeder.');
            }

            // 2) Sedes (por nombre exacto del SedeSeeder)
            $sedes = Sede::query()
                ->where('universidad_id', $uni->id)
                ->whereIn('nombre', ['Lima', 'Juliaca', 'Tarapoto'])
                ->get()
                ->keyBy('nombre');

            foreach (['Lima','Juliaca','Tarapoto'] as $sn) {
                if (! isset($sedes[$sn])) {
                    throw new RuntimeException("Sede {$sn} no encontrada. Ejecuta SedeSeeder primero.");
                }
            }

            // 3) Facultades por código
            $facultades = Facultad::query()
                ->where('universidad_id', $uni->id)
                ->whereIn('codigo', ['FIA','FCE','FCS','FACIHED','FAT'])
                ->get()
                ->keyBy('codigo');

            foreach (['FIA','FCE','FCS','FACIHED','FAT'] as $fc) {
                if (! isset($facultades[$fc])) {
                    throw new RuntimeException("Facultad {$fc} no encontrada. Ejecuta FacultadSeeder primero.");
                }
            }

            // 4) EP por (facultad, código) para acceso rápido
            $eps = EscuelaProfesional::query()
                ->select(['escuelas_profesionales.*', 'facultades.codigo as fac_codigo'])
                ->join('facultades', 'facultades.id', '=', 'escuelas_profesionales.facultad_id')
                ->where('facultades.universidad_id', $uni->id)
                ->get()
                ->groupBy('fac_codigo'); // ->get('FIA') etc.

            $getEpId = function (string $facCodigo, string $epCodigo) use ($eps): int {
                $facCodigo = strtoupper(trim($facCodigo));
                $epCodigo  = strtoupper(trim($epCodigo));
                $list = $eps->get($facCodigo);
                if (! $list) {
                    throw new RuntimeException("No hay EPs cargadas para la facultad {$facCodigo}.");
                }
                /** @var \App\Models\EscuelaProfesional|null $row */
                $row = $list->firstWhere('codigo', $epCodigo);
                if (! $row) {
                    throw new RuntimeException("EP código {$epCodigo} no encontrada en facultad {$facCodigo}.");
                }
                return (int) $row->id;
            };

            // 5) Ofertas por sede (SIN solape)
            //    Puedes ajustar las fechas a tu realidad.
            $ofertas = [
                // ---------- LIMA (principal) ----------
                'Lima' => [
                    // FIA
                    ['fac' => 'FIA', 'ep' => 'SIS',    'desde' => '2020-01-01', 'hasta' => null],
                    ['fac' => 'FIA', 'ep' => 'CIV',    'desde' => '2020-01-01', 'hasta' => null],
                    ['fac' => 'FIA', 'ep' => 'IND',    'desde' => '2020-01-01', 'hasta' => null],
                    ['fac' => 'FIA', 'ep' => 'ARQ',    'desde' => '2021-03-01', 'hasta' => null],
                    // FCE
                    ['fac' => 'FCE', 'ep' => 'ADM',    'desde' => '2020-01-01', 'hasta' => null],
                    ['fac' => 'FCE', 'ep' => 'CON',    'desde' => '2020-01-01', 'hasta' => null],
                    ['fac' => 'FCE', 'ep' => 'NEGINT', 'desde' => '2022-08-01', 'hasta' => null],
                    ['fac' => 'FCE', 'ep' => 'MK',     'desde' => '2021-01-01', 'hasta' => '2023-12-31'], // ejemplo de cierre
                    ['fac' => 'FCE', 'ep' => 'MK',     'desde' => '2024-03-01', 'hasta' => null],         // re-apertura, sin solape
                    // FCS
                    ['fac' => 'FCS', 'ep' => 'ENF',    'desde' => '2019-01-01', 'hasta' => null],
                    ['fac' => 'FCS', 'ep' => 'NUT',    'desde' => '2019-01-01', 'hasta' => null],
                    ['fac' => 'FCS', 'ep' => 'PSI',    'desde' => '2020-01-01', 'hasta' => null],
                    // FACIHED
                    ['fac' => 'FACIHED', 'ep' => 'EDU-PRI', 'desde' => '2018-01-01', 'hasta' => null],
                    ['fac' => 'FACIHED', 'ep' => 'EDU-SEC', 'desde' => '2018-01-01', 'hasta' => null],
                    ['fac' => 'FACIHED', 'ep' => 'COM',     'desde' => '2021-01-01', 'hasta' => null],
                    // FAT
                    ['fac' => 'FAT', 'ep' => 'TEO',    'desde' => '2010-01-01', 'hasta' => null],
                ],

                // ---------- JULIACA ----------
                'Juliaca' => [
                    ['fac' => 'FIA', 'ep' => 'SIS', 'desde' => '2020-03-01', 'hasta' => null],
                    ['fac' => 'FIA', 'ep' => 'CIV', 'desde' => '2021-01-01', 'hasta' => null],
                    ['fac' => 'FCE', 'ep' => 'ADM', 'desde' => '2019-01-01', 'hasta' => null],
                    ['fac' => 'FCE', 'ep' => 'CON', 'desde' => '2019-01-01', 'hasta' => null],
                    ['fac' => 'FCS', 'ep' => 'ENF', 'desde' => '2019-01-01', 'hasta' => null],
                    ['fac' => 'FCS', 'ep' => 'PSI', 'desde' => '2020-01-01', 'hasta' => null],
                    ['fac' => 'FACIHED', 'ep' => 'EDU-PRI', 'desde' => '2018-01-01', 'hasta' => null],
                    ['fac' => 'FAT', 'ep' => 'TEO', 'desde' => '2010-01-01', 'hasta' => null],
                ],

                // ---------- TARAPOTO ----------
                'Tarapoto' => [
                    ['fac' => 'FIA', 'ep' => 'SIS', 'desde' => '2021-03-01', 'hasta' => null],
                    ['fac' => 'FCE', 'ep' => 'ADM', 'desde' => '2019-01-01', 'hasta' => null],
                    ['fac' => 'FCE', 'ep' => 'CON', 'desde' => '2019-01-01', 'hasta' => null],
                    ['fac' => 'FCS', 'ep' => 'NUT', 'desde' => '2020-01-01', 'hasta' => null],
                    ['fac' => 'FACIHED', 'ep' => 'COM', 'desde' => '2022-01-01', 'hasta' => null],
                    ['fac' => 'FAT', 'ep' => 'TEO', 'desde' => '2010-01-01', 'hasta' => null],
                ],
            ];

            // 6) Insertar/actualizar sin solape
            foreach ($ofertas as $sedeNombre => $items) {
                $sedeId = (int) $sedes[$sedeNombre]->id;

                foreach ($items as $it) {
                    $epId = $getEpId($it['fac'], $it['ep']);
                    $desde = $it['desde'];        // 'Y-m-d' o null
                    $hasta = $it['hasta'];        // 'Y-m-d' o null

                    // Valida ausencia de solapamiento con otros registros (misma EP y sede)
                    $this->assertNoOverlap($epId, $sedeId, $desde, $hasta);

                    EpSede::updateOrCreate(
                        [
                            'escuela_profesional_id' => $epId,
                            'sede_id'                => $sedeId,
                            'vigente_desde'          => $desde, // UNIQUE (ep, sede, desde)
                        ],
                        [
                            'vigente_hasta'          => $hasta,
                        ]
                    );
                }
            }
        });
    }

    /**
     * Regla de no solapamiento: [a,b] cruza [c,d] ↔ a<=d AND c<=b (NULL = infinito).
     * Si encuentra cruce, lanza ValidationException (falla el seeder).
     */
    private function assertNoOverlap(int $epId, int $sedeId, ?string $desde, ?string $hasta, ?int $ignoreId = null): void
    {
        $q = EpSede::query()
            ->where('escuela_profesional_id', $epId)
            ->where('sede_id', $sedeId)
            ->where(function ($qq) use ($desde, $hasta) {
                $qq->where(function ($q1) use ($hasta) {
                    $q1->whereNull('vigente_desde')->orWhere('vigente_desde', '<=', $hasta ?? '9999-12-31');
                })->where(function ($q2) use ($desde) {
                    $q2->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $desde ?? '0001-01-01');
                });
            });

        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }

        if ($q->exists()) {
            throw ValidationException::withMessages([
                'rango' => 'El rango de vigencia para esta EP y sede se solapa con otro registro existente.',
            ]);
        }
    }
}
