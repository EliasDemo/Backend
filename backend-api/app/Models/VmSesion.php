<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{MorphTo, HasMany};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VmSesion extends Model
{
    use HasFactory;

    protected $table = 'vm_sesiones';

    protected $fillable = [
        'sessionable_type','sessionable_id',
        'fecha','hora_inicio','hora_fin',
        'latitud','longitud','geofence_radio_m','requiere_gps',
        'codigo_manual','estado',
    ];

    protected $casts = [
        'fecha'        => 'date',
        'hora_inicio'  => 'datetime:H:i',
        'hora_fin'     => 'datetime:H:i',
        'requiere_gps' => 'bool',
        'latitud'      => 'float',
        'longitud'     => 'float',
    ];

    public function sessionable(): MorphTo { return $this->morphTo(); }

    public function tokens(): HasMany { return $this->hasMany(VmQrToken::class, 'sesion_id'); }
    public function asistencias(): HasMany { return $this->hasMany(VmAsistencia::class, 'sesion_id'); }
    public function registrosHoras(): HasMany { return $this->hasMany(RegistroHora::class, 'sesion_id'); }

    public function scopeAbiertas($q) { return $q->where('estado','ABIERTA'); }
}
