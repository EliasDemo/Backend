<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Eloquent
 */
class Universidad extends Model
{
    use HasFactory;

    protected $table = 'universidades';

    protected $fillable = [
        'codigo_entidad',
        'nombre',
        'tipo_gestion',
        'estado_licenciamiento',
        'periodo_licenciamiento',
        'departamento_local',
        'provincia_local',
        'distrito_local',
        'latitud_ubicacion',
        'longitud_ubicacion',
        'fecha_licenciamiento',
        'resolucion_licenciamiento',
        'sigla',
        'ruc',
        'domicilio_legal',
        'web_url',
        'telefono',
        'email_contacto',
        'licencia_vigencia_desde',
        'licencia_vigencia_hasta',
    ];

    protected $casts = [
        'fecha_licenciamiento'     => 'date',
        'licencia_vigencia_desde'  => 'date',
        'licencia_vigencia_hasta'  => 'date',
        'latitud_ubicacion'        => 'float',
        'longitud_ubicacion'       => 'float',
    ];

    // === Scopes útiles ===
    public function scopeLicenciadas($q) {
        return $q->where('estado_licenciamiento', 'LICENCIA_OTORGADA');
    }

    public function scopePublicas($q)  { return $q->where('tipo_gestion', 'PUBLICO'); }
    public function scopePrivadas($q)  { return $q->where('tipo_gestion', 'PRIVADO'); }

    public function sedes()    { return $this->hasMany(Sede::class); }
    public function facultades(){ return $this->hasMany(Facultad::class); }
}
