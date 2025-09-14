<?php

namespace Database\Seeders;

use App\Models\Sede;
use App\Models\Universidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SedeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Busca la UPEU creada por tu UniversidadSeeder
            $universidad = Universidad::query()
                ->where('sigla', 'UPEU')
                ->orWhere('nombre', 'UNIVERSIDAD PERUANA UNIÓN')
                ->first();

            if (! $universidad) {
                // Evita sembrar sedes “huérfanas”
                throw new RuntimeException('Universidad UPEU no encontrada. Ejecuta primero UniversidadSeeder.');
            }

            // Sedes base (todas activas por default)
            $sedes = [
                ['nombre' => 'Lima',     'es_principal' => true,  'esta_suspendida' => false],
                ['nombre' => 'Juliaca',  'es_principal' => false, 'esta_suspendida' => false],
                ['nombre' => 'Tarapoto', 'es_principal' => false, 'esta_suspendida' => false],
            ];

            // Garantiza solo UNA principal (no tocamos suspensión globalmente)
            Sede::where('universidad_id', $universidad->id)->update(['es_principal' => false]);

            foreach ($sedes as $sede) {
                Sede::updateOrCreate(
                    [
                        'universidad_id' => $universidad->id,
                        'nombre'         => $sede['nombre'],   // respeta el unique(universidad_id, nombre)
                    ],
                    [
                        'es_principal'     => $sede['es_principal'],
                        'esta_suspendida'  => $sede['esta_suspendida'],
                        'suspendida_desde' => $sede['esta_suspendida'] ? now() : null,
                    ]
                );
            }
        });
    }
}
