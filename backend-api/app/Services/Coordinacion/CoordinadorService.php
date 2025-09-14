<?php

namespace App\Services\Coordinacion;

use App\Models\CoordinadorEpSede;
use App\Models\PeriodoAcademico;
use App\Services\Personas\PersonaService;
use App\Services\Auth\UserProvisioningService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CoordinadorService
{
    public function __construct(
        private readonly PersonaService $personas,
        private readonly UserProvisioningService $provision
    ) {}

    public function register(array $personaData, int $epSedeId): array
    {
        return DB::transaction(function () use ($personaData, $epSedeId) {
            $periodo = PeriodoAcademico::query()->actual()->first();
            if (! $periodo) throw new RuntimeException('No existe periodo académico marcado como actual.');

            $persona = $this->personas->upsertFromDTO($personaData);
            [$user, $passwordTemporal] = $this->provision->provisionForPersona($persona, 'Coordinador');

            $row = CoordinadorEpSede::updateOrCreate(
                ['ep_sede_id' => $epSedeId, 'periodo_id' => $periodo->id],
                ['persona_id' => $persona->id, 'cargo' => 'COORDINADOR EP', 'activo' => true]
            );

            return compact('row', 'user', 'passwordTemporal', 'persona');
        });
    }
}
