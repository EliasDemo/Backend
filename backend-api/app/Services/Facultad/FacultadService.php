<?php

namespace App\Services\Facultad;

use App\Models\Facultad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacultadService
{
    public function baseQuery(): Builder
    {
        $fixed = (int) config('universidad.fixed_id', 1);

        return Facultad::query()
            ->where('universidad_id', $fixed)
            ->withCount('escuelas');
    }

    public function create(array $data): Facultad
    {
        $fixed = (int) config('universidad.fixed_id', 1);
        $payload = [
            'universidad_id'   => $fixed,
            'codigo'           => $data['codigo'],
            'nombre'           => $data['nombre'],
            'slug'             => $data['slug'] ?? null,
            'esta_suspendida'  => false,
            'suspendida_desde' => null,
        ];

        return DB::transaction(fn () => Facultad::create($payload));
    }

    public function update(Facultad $row, array $data): Facultad
    {
        return DB::transaction(function () use ($row, $data) {
            $row->update($data);
            return $row->fresh(['escuelas']);
        });
    }

    /** Regla de negocio: no suspender si tiene escuelas activas */
    public function suspender(Facultad $row): Facultad
    {
        $hasActiveEscuelas = $row->escuelas()->where('esta_suspendida', false)->exists();
        if ($hasActiveEscuelas) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede suspender: existen escuelas activas en la facultad.',
            ]);
        }

        $row->update(['esta_suspendida' => true, 'suspendida_desde' => now()]);
        return $row->fresh();
    }

    public function restaurar(Facultad $row): Facultad
    {
        $row->update(['esta_suspendida' => false, 'suspendida_desde' => null]);
        return $row->fresh();
    }
}
