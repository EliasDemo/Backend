<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphMany, HasMany};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VmProyecto extends Model
{
    use HasFactory;

    protected $table = 'vm_proyectos';

    protected $fillable = [
        'ep_sede_id','periodo_id','codigo','titulo','descripcion',
        'tipo','modalidad','estado','horas_planificadas','horas_minimas_participante',
        'fecha_inicio','fecha_fin','lugar','latitud','longitud','geofence_radio_m',
        'entidad_aliada','contacto_externo','telefono_externo','email_externo',
        'responsable_persona_id','creado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'latitud'      => 'float',
        'longitud'     => 'float',
    ];

    // Relaciones
    public function epSede(): BelongsTo { return $this->belongsTo(EpSede::class); }
    public function periodo(): BelongsTo { return $this->belongsTo(PeriodoAcademico::class, 'periodo_id'); }
    public function responsable(): BelongsTo { return $this->belongsTo(Persona::class, 'responsable_persona_id'); }
    public function creador(): BelongsTo { return $this->belongsTo(User::class, 'creado_por'); }

    public function eventos(): HasMany { return $this->hasMany(VmEvento::class, 'proyecto_id'); }
    public function sesiones(): MorphMany { return $this->morphMany(VmSesion::class, 'sessionable'); }
    public function participaciones(): MorphMany { return $this->morphMany(VmParticipacion::class, 'participable'); }
    public function imagenes(): MorphMany { return $this->morphMany(Imagen::class, 'imageable'); }
    public function certificados(): MorphMany { return $this->morphMany(Certificado::class, 'certificable'); }
    public function registroHoras(): MorphMany { return $this->morphMany(RegistroHora::class, 'vinculable'); }

    // Scopes
    public function scopeDePeriodo($q, int $periodoId) { return $q->where('periodo_id', $periodoId); }
    public function scopePublicados($q) { return $q->where('estado','PUBLICADO'); }
}
