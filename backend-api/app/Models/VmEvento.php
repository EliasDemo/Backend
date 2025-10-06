<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphMany};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VmEvento extends Model
{
    use HasFactory;

    protected $table = 'vm_eventos';

    protected $fillable = [
        'ep_sede_id','periodo_id','proyecto_id','codigo','titulo','descripcion',
        'tipo_evento','fecha','hora_inicio','hora_fin',
        'latitud','longitud','geofence_radio_m','requiere_gps',
        'cupo_maximo','requiere_inscripcion','estado',
    ];

    protected $casts = [
        'fecha'        => 'date',
        'hora_inicio'  => 'datetime:H:i',
        'hora_fin'     => 'datetime:H:i',
        'requiere_gps' => 'bool',
        'latitud'      => 'float',
        'longitud'     => 'float',
    ];

    public function epSede(): BelongsTo { return $this->belongsTo(EpSede::class); }
    public function periodo(): BelongsTo { return $this->belongsTo(PeriodoAcademico::class, 'periodo_id'); }
    public function proyecto(): BelongsTo { return $this->belongsTo(VmProyecto::class, 'proyecto_id'); }

    public function sesiones(): MorphMany { return $this->morphMany(VmSesion::class, 'sessionable'); }
    public function participaciones(): MorphMany { return $this->morphMany(VmParticipacion::class, 'participable'); }
    public function imagenes(): MorphMany { return $this->morphMany(Imagen::class, 'imageable'); }
    public function certificados(): MorphMany { return $this->morphMany(Certificado::class, 'certificable'); }
    public function registroHoras(): MorphMany { return $this->morphMany(RegistroHora::class, 'vinculable'); }
}
