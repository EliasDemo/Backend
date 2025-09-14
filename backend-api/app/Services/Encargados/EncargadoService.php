<?php

namespace App\Services\Encargados;

use App\Models\EncargadoSede;
use App\Models\PeriodoAcademico;
use App\Services\Personas\PersonaService;
use App\Services\Auth\UserProvisioningService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EncargadoService
{
    public function __construct(
        private readonly PersonaService $personas,
        private readonly UserProvisioningService $provision
    ) {}

    public function register(array $personaData, int $sedeId): array
    {
        return DB::transaction(function () use ($personaData, $sedeId) {
            $periodo = PeriodoAcademico::query()->actual()->first();
            if (! $periodo) throw new RuntimeException('No existe periodo académico marcado como actual.');

            $persona = $this->personas->upsertFromDTO($personaData);
            [$user, $passwordTemporal] = $this->provision->provisionForPersona($persona, 'Encargado');

            $row = EncargadoSede::updateOrCreate(
                ['sede_id' => $sedeId, 'periodo_id' => $periodo->id],
                ['persona_id' => $persona->id, 'cargo' => 'ENCARGADO DE SEDE', 'activo' => true]
            );

            return compact('row', 'user', 'passwordTemporal', 'persona');
        });
    }
}
