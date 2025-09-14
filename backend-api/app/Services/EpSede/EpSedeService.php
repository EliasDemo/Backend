<?php

namespace App\Services\EpSede;

use App\Models\EpSede;
use App\Models\EscuelaProfesional;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EpSedeService
{
    /** Consulta base con relaciones opcionales */
    public function baseQuery(): Builder
    {
        return EpSede::query()
            ->with(['ep:id,facultad_id,codigo,nombre', 'sede:id,universidad_id,nombre']);
    }

    /** Crear relación EP–Sede con validación de no solapamiento */
    public function create(array $data): EpSede
    {
        $epId   = (int) $data['escuela_profesional_id'];
        $sedeId = (int) $data['sede_id'];
        $desde  = $data['vigente_desde'] ?? null;
        $hasta  = $data['vigente_hasta'] ?? null;

        $this->assertEpAndSedeExist($epId, $sedeId);
        $this->assertSameUniversity($epId, $sedeId);
        $this->assertNoOverlap($epId, $sedeId, $desde, $hasta, null);

        return DB::transaction(function () use ($data) {
            return EpSede::create($data);
        });
    }

    /** Actualizar relación EP–Sede con validación de no solapamiento */
    public function update(EpSede $row, array $data): EpSede
    {
        $epId   = (int) ($data['escuela_profesional_id'] ?? $row->escuela_profesional_id);
        $sedeId = (int) ($data['sede_id']                 ?? $row->sede_id);
        $desde  = $data['vigente_desde'] ?? ($row->vigente_desde?->toDateString());
        $hasta  = $data['vigente_hasta'] ?? ($row->vigente_hasta?->toDateString());

        $this->assertEpAndSedeExist($epId, $sedeId);
        $this->assertSameUniversity($epId, $sedeId);
        $this->assertNoOverlap($epId, $sedeId, $desde, $hasta, $row->id);

        return DB::transaction(function () use ($row, $data) {
            $row->update($data);
            return $row->fresh(['ep','sede']);
        });
    }

    /** Cerrar oferta (setear vigente_hasta) */
    public function close(EpSede $row, ?string $hasta = null): EpSede
    {
        $hasta = $hasta ?: now()->toDateString();
        $desde = $row->vigente_desde?->toDateString();

        if ($desde && $hasta < $desde) {
            throw ValidationException::withMessages([
                'vigente_hasta' => 'La fecha de cierre no puede ser anterior a la fecha de inicio.',
            ]);
        }

        $row->update(['vigente_hasta' => $hasta]);
        return $row->fresh(['ep','sede']);
    }

    /** ------- Validaciones auxiliares ------- */

    private function assertEpAndSedeExist(int $epId, int $sedeId): void
    {
        if (!EscuelaProfesional::query()->whereKey($epId)->exists()) {
            throw ValidationException::withMessages(['escuela_profesional_id' => 'Escuela Profesional no existe.']);
        }
        if (!Sede::query()->whereKey($sedeId)->exists()) {
            throw ValidationException::withMessages(['sede_id' => 'Sede no existe.']);
        }
    }

    /** (Opcional) Asegura que EP y Sede pertenezcan a la misma Universidad fija */
    private function assertSameUniversity(int $epId, int $sedeId): void
    {
        $fixed = (int) config('universidad.fixed_id', 1);

        $epFacUni = EscuelaProfesional::query()
            ->select('facultades.universidad_id')
            ->join('facultades', 'facultades.id', '=', 'escuelas_profesionales.facultad_id')
            ->where('escuelas_profesionales.id', $epId)
            ->value('facultades.universidad_id');

        $sedeUni = Sede::query()->whereKey($sedeId)->value('universidad_id');

        if ($epFacUni !== $fixed || $sedeUni !== $fixed) {
            throw ValidationException::withMessages([
                'universidad' => 'La EP y la Sede deben pertenecer a la universidad configurada.',
            ]);
        }
    }

    /** Tu método: valida que no exista solapamiento de vigencias para la misma EP y Sede */
    private function assertNoOverlap(int $epId, int $sedeId, ?string $desde, ?string $hasta, ?int $ignoreId = null): void
    {
        $q = EpSede::query()
            ->where('escuela_profesional_id', $epId)
            ->where('sede_id', $sedeId)
            // Solapamiento si: [a,b] cruza [c,d] <=> a<=d AND c<=b (NULL = infinito)
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
                'rango' => 'El rango de vigencia se solapa con otro registro existente para la misma EP y sede.',
            ]);
        }
    }
}
