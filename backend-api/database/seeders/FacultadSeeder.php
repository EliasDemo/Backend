<?php

namespace Database\Seeders;

use App\Models\Facultad;
use App\Models\Universidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class FacultadSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $uni = Universidad::query()
                ->where('sigla', 'UPEU')
                ->orWhere('nombre', 'UNIVERSIDAD PERUANA UNIÓN')
                ->first();

            if (! $uni) {
                throw new RuntimeException('Universidad UPEU no encontrada. Ejecuta primero UniversidadSeeder.');
            }

            $facultades = [
                ['codigo' => 'FIA',     'nombre' => 'FACULTAD DE INGENIERÍA Y ARQUITECTURA'],
                ['codigo' => 'FCE',     'nombre' => 'FACULTAD DE CIENCIAS EMPRESARIALES'],
                ['codigo' => 'FCS',     'nombre' => 'FACULTAD DE CIENCIAS DE LA SALUD'],
                ['codigo' => 'FACIHED', 'nombre' => 'FACULTAD DE CIENCIAS HUMANAS Y EDUCACIÓN'],
                ['codigo' => 'FAT',     'nombre' => 'FACULTAD DE TEOLOGÍA'],
            ];

            foreach ($facultades as $f) {
                $codigo = strtoupper(trim($f['codigo']));
                $nombre = trim($f['nombre']);
                $slug   = Str::slug($nombre);

                Facultad::updateOrCreate(
                    ['universidad_id' => $uni->id, 'codigo' => $codigo],
                    [
                        'nombre'            => $nombre,
                        'slug'              => $slug,
                        'esta_suspendida'   => false,
                        'suspendida_desde'  => null,
                    ]
                );
            }
        });
    }
}
