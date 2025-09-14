<?php

namespace App\Services\Escuela;

use App\Models\EscuelaProfesional;
use App\Models\Facultad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EscuelaService
{
    public function baseQuery(): Builder
    {
        $fixed = (int) config('universidad.fixed_id', 1);

        return EscuelaProfesional::query()
            ->select('escuelas_profesionales.*')
            ->join('facultades', 'facultades.id', '=', 'escuelas_profesionales.facultad_id')
            ->where('facultades.universidad_id', $fixed)
            ->with(['facultad']);
    }

    /** Verifica que la facultad pertenezca a la universidad fija */
    private function assertFacultadInFixedUniversity(int $facultadId): void
    {
        $fixed = (int) config('universidad.fixed_id', 1);

        $facUni = Facultad::query()
            ->whereKey($facultadId)
            ->value('universidad_id');

        if ($facUni !== $fixed) {
            throw ValidationException::withMessages([
                'facultad_id' => 'La facultad no pertenece a la universidad configurada.',
            ]);
        }
    }

    public function create(array $data): EscuelaProfesional
    {
        $this->assertFacultadInFixedUniversity((int) $data['facultad_id']);

        return DB::transaction(fn () => EscuelaProfesional::create([
            'facultad_id'       => (int) $data['facultad_id'],
            'codigo'            => $data['codigo'],
            'nombre'            => $data['nombre'],
            'slug'              => $data['slug'] ?? null,
            'esta_suspendida'   => false,
            'suspendida_desde'  => null,
        ]));
    }

    public function update(EscuelaProfesional $row, array $data): EscuelaProfesional
    {
        if (isset($data['facultad_id'])) {
            $this->assertFacultadInFixedUniversity((int) $data['facultad_id']);
        }

        return DB::transaction(function () use ($row, $data) {
            $row->update($data);
            return $row->fresh(['facultad']);
        });
    }

    /** (Opcional) No suspender si tiene ofertas vigentes en ep_sede */
    public function suspender(EscuelaProfesional $row): EscuelaProfesional
    {
        $hasVigentes = $row->sedes()
            ->where(function ($q) {
                $hoy = now()->toDateString();
                $q->whereNull('ep_sede.vigente_desde')->orWhere('ep_sede.vigente_desde', '<=', $hoy);
            })
            ->where(function ($q) {
                $hoy = now()->toDateString();
                $q->whereNull('ep_sede.vigente_hasta')->orWhere('ep_sede.vigente_hasta', '>=', $hoy);
            })
            ->exists();

        if ($hasVigentes) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede suspender: existen ofertas vigentes (EP–Sede).',
            ]);
        }

        $row->update(['esta_suspendida' => true, 'suspendida_desde' => now()]);
        return $row->fresh();
    }

    public function restaurar(EscuelaProfesional $row): EscuelaProfesional
    {
        $row->update(['esta_suspendida' => false, 'suspendida_desde' => null]);
        return $row->fresh();
    }
}
