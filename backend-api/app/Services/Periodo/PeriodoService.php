<?php

namespace App\Services\Periodo;

use App\Models\PeriodoAcademico;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PeriodoService
{
    public function baseQuery(): Builder
    {
        return PeriodoAcademico::query();
    }

    public function create(array $data): PeriodoAcademico
    {
        $this->assertNoOverlap($data['fecha_inicio'], $data['fecha_fin'], null);

        return DB::transaction(function () use ($data) {
            $p = PeriodoAcademico::create($data);
            if (!empty($data['es_actual'])) {
                $this->marcarComoActual($p);
            }
            return $p->fresh();
        });
    }

    public function update(PeriodoAcademico $row, array $data): PeriodoAcademico
    {
        $desde = $data['fecha_inicio'] ?? $row->fecha_inicio->toDateString();
        $hasta = $data['fecha_fin']    ?? $row->fecha_fin->toDateString();

        $this->assertNoOverlap($desde, $hasta, $row->id);

        return DB::transaction(function () use ($row, $data) {
            $row->update($data);
            if (array_key_exists('es_actual', $data) && $data['es_actual']) {
                $this->marcarComoActual($row);
            }
            return $row->fresh();
        });
    }

    /** Marca el periodo como actual y desmarca el resto */
    public function marcarComoActual(PeriodoAcademico $row): void
    {
        DB::transaction(function () use ($row) {
            PeriodoAcademico::where('es_actual', true)
                ->where('id', '!=', $row->id)
                ->update(['es_actual' => false]);

            // (Opcional) sincronizar estado con fechas
            $estado = $this->estadoSegunFechas($row->fecha_inicio, $row->fecha_fin);
            $row->update(['es_actual' => true, 'estado' => $estado]);
        });
    }

    /** Determina estado a partir de fechas */
    public function estadoSegunFechas($inicio, $fin): string
    {
        $hoy = Carbon::today();
        if ($hoy->lt(Carbon::parse($inicio))) return 'PLANIFICADO';
        if ($hoy->gt(Carbon::parse($fin)))    return 'CERRADO';
        return 'EN_CURSO';
    }

    /** No solapamiento con otros periodos */
    private function assertNoOverlap(string $desde, string $hasta, ?int $ignoreId): void
    {
        $q = PeriodoAcademico::query()
            ->where('fecha_inicio', '<=', $hasta)
            ->where('fecha_fin',    '>=', $desde);

        if ($ignoreId) $q->where('id', '!=', $ignoreId);

        if ($q->exists()) {
            throw ValidationException::withMessages([
                'rango' => 'Las fechas se solapan con otro periodo académico.',
            ]);
        }
    }
}
