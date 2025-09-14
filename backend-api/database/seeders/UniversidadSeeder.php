<?php

namespace Database\Seeders;

use App\Models\Universidad;
use Illuminate\Database\Seeder;

class UniversidadSeeder extends Seeder
{
    public function run(): void
    {
        Universidad::updateOrCreate(
            ['codigo_entidad' => 'UPEU-054-2018'], // <-- placeholder único
            [
                'nombre'                 => 'UNIVERSIDAD PERUANA UNIÓN',
                'sigla'                  => 'UPEU',
                'tipo_gestion'           => 'PRIVADO',
                'estado_licenciamiento'  => 'LICENCIA_OTORGADA',
                'periodo_licenciamiento' => 6,                      // 6 años
                'fecha_licenciamiento'   => '2018-06-07',           // publicación en El Peruano
                'resolucion_licenciamiento' => 'RCD N° 054-2018-SUNEDU/CD',

                // Local principal (Lima, Ñaña)
                'departamento_local'     => 'Lima',
                'provincia_local'        => 'Lima',
                'distrito_local'         => 'Lurigancho-Chosica',

                // Coordenadas del local principal (si las confirmas, colócalas)
                'latitud_ubicacion'      => null,
                'longitud_ubicacion'     => null,

                // Contacto institucional
                'web_url'                => 'https://upeu.edu.pe',
                'telefono'               => '+51 1 618 6300',
                // Correo genérico frecuente en páginas oficiales UPeU; ajusta si usas otro buzón institucional
                'email_contacto'         => 'informes@upeu.edu.pe',

                'domicilio_legal'        => 'Km 19 Carretera Central, Ñaña, Lurigancho, Lima, Perú',
                'ruc'                    => '20138122256',          // SUNAT

                // Vigencia (opcional; no siempre la publica SUNEDU, pero tu esquema lo permite)
                'licencia_vigencia_desde'=> '2018-06-07',
                'licencia_vigencia_hasta'=> '2024-06-07',
            ]
        );
    }
}
