<?php

namespace App\Services\Sede;

use App\Models\Sede;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SedeService
{
    private int $universidadId;

    public function __construct(?int $universidadId = null)
    {
        // Si no te inyectan un valor, toma el de config
        $this->universidadId = $universidadId ?? (int) config('universidad.fixed_id', 1);
    }

    public function baseQuery(): Builder
    {
        return Sede::query()->where('universidad_id', $this->universidadId);
    }

    public function ensureBelongs(Sede $sede): void
    {
        if ((int) $sede->universidad_id !== $this->universidadId) {
            abort(404);
        }
    }

    public function create(array $data): Sede
    {
        return DB::transaction(function () use ($data) {
            $esPrincipal = (bool)($data['es_principal'] ?? false);

            if ($esPrincipal) {
                $this->baseQuery()->update(['es_principal' => false]);
            }

            return Sede::create([
                'universidad_id' => $this->universidadId,
                'nombre'         => $data['nombre'],
                'es_principal'   => $esPrincipal,
            ]);
        });
    }

    public function update(Sede $sede, array $data): Sede
    {
        $this->ensureBelongs($sede);

        return DB::transaction(function () use ($sede, $data) {
            if (array_key_exists('es_principal', $data) && $data['es_principal']) {
                $this->baseQuery()->update(['es_principal' => false]);
            }

            $sede->update($data);
            return $sede->fresh();
        });
    }

    public function makePrincipal(Sede $sede): Sede
    {
        $this->ensureBelongs($sede);

        return DB::transaction(function () use ($sede) {
            $this->baseQuery()->update(['es_principal' => false]);
            $sede->update(['es_principal' => true]);
            return $sede->fresh();
        });
    }

    public function suspend(Sede $sede): Sede
    {
        $this->ensureBelongs($sede);

        if ($sede->es_principal) {
            throw new HttpException(422, 'No se puede suspender la sede principal. Asigna otra sede principal primero.');
        }

        if (! $sede->esta_suspendida) {
            $sede->suspender(); // helper del modelo
        }

        return $sede->fresh();
    }

    public function restore(Sede $sede): Sede
    {
        $this->ensureBelongs($sede);

        if ($sede->esta_suspendida) {
            $sede->restaurar(); // helper del modelo
        }

        return $sede->fresh();
    }
}
