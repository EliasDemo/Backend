<?php

namespace App\Services\Estudiantes;

use App\Models\Estudiante;
use App\Services\Personas\PersonaService;
use App\Services\Auth\UserProvisioningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EstudianteService
{
    public function __construct(
        private readonly PersonaService $personas,
        private readonly UserProvisioningService $provision
    ) {}

    public function register(array $personaData, array $academicData): array
    {
        return DB::transaction(function () use ($personaData, $academicData) {
            $persona = $this->personas->upsertFromDTO($personaData);

            $codigo = $academicData['codigo'] ?? $this->makeCodigo();

            $row = Estudiante::firstOrCreate(
                ['codigo' => Str::upper(trim($codigo))],
                [
                    'persona_id'         => $persona->id,
                    'ep_sede_id'         => (int) $academicData['ep_sede_id'],
                    'ingreso_periodo_id' => $academicData['ingreso_periodo_id'] ?? null,
                    'estado'             => 'ACTIVO',
                    'ciclo_actual'       => $academicData['ciclo_actual'] ?? null,
                    'cohorte_codigo'     => $academicData['cohorte_codigo'] ?? null,
                ]
            );

            [$user, $passwordTemporal] = $this->provision->provisionForPersona($persona, 'Estudiante');

            return compact('row', 'user', 'passwordTemporal', 'persona');
        });
    }

    private function makeCodigo(): string
    {
        do {
            $candidate = 'E' . date('y') . random_int(1000, 9999);
        } while (Estudiante::where('codigo', $candidate)->exists());
        return $candidate;
    }
}
