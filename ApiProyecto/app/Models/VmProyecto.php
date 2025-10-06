<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VmProyecto extends Model
{
    use HasFactory;

    protected $table = 'vm_proyectos';

    protected $fillable = [
        'ep_sede_id',
        'periodo_id',
        'codigo',
        'titulo',
        'descripcion',
        'tipo',
        'modalidad',
        'estado',
        'horas_planificadas',
        'horas_minimas_participante',
    ];

    protected $casts = [
        'horas_planificadas'          => 'integer',
        'horas_minimas_participante'  => 'integer',
    ];

    /* =====================
     | Relaciones
     |=====================*/
    public function epSede()
    {
        return $this->belongsTo(EpSede::class, 'ep_sede_id');
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    public function procesos()
    {
        return $this->hasMany(VmProceso::class, 'proyecto_id');
    }

    // Participaciones (polimórfica)
    public function participaciones()
    {
        return $this->morphMany(VmParticipacion::class, 'participable');
    }

    // Certificados (polimórfica)
    public function certificados()
    {
        return $this->morphMany(Certificado::class, 'certificable');
    }

    // Imágenes (polimórfica)
    public function imagenes()
    {
        return $this->morphMany(Imagen::class, 'imageable');
    }

    // Registro de horas (polimórfica vinculable)
    public function registrosHoras()
    {
        return $this->morphMany(RegistroHora::class, 'vinculable');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeEnCurso($q)      { return $q->where('estado', 'EN_CURSO'); }
    public function scopePlanificados($q) { return $q->where('estado', 'PLANIFICADO'); }
    public function scopeDelPeriodo($q, int $periodoId) { return $q->where('periodo_id', $periodoId); }
    public function scopeDeEpSede($q, int $epSedeId)    { return $q->where('ep_sede_id', $epSedeId); }
}
