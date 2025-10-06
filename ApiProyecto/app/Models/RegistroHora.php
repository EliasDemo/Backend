<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroHora extends Model
{
    use HasFactory;

    protected $table = 'registro_horas';

    protected $fillable = [
        'expediente_id',
        'ep_sede_id',
        'periodo_id',
        'fecha',
        'minutos',
        'actividad',
        'estado',
        'vinculable_id',
        'vinculable_type',
        'sesion_id',
        'asistencia_id',
    ];

    protected $casts = [
        'fecha'   => 'date',
        'minutos' => 'integer',
    ];

    /* =====================
     | Relaciones
     |=====================*/
    public function expediente()
    {
        return $this->belongsTo(ExpedienteAcademico::class, 'expediente_id');
    }

    public function epSede()
    {
        return $this->belongsTo(EpSede::class, 'ep_sede_id');
    }

    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    // Polimórfica: Proyecto/Evento u otros
    public function vinculable()
    {
        return $this->morphTo();
    }

    public function sesion()
    {
        return $this->belongsTo(VmSesion::class, 'sesion_id');
    }

    public function asistencia()
    {
        return $this->belongsTo(VmAsistencia::class, 'asistencia_id');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeDeExpediente($q, int $expedienteId)
    {
        return $q->where('expediente_id', $expedienteId);
    }

    public function scopeDePeriodo($q, int $periodoId)
    {
        return $q->where('periodo_id', $periodoId);
    }

    public function scopeEnFecha($q, $fecha)
    {
        return $q->whereDate('fecha', $fecha);
    }

    public function scopeEstado($q, string $estado)
    {
        return $q->where('estado', $estado);
    }

    /* =====================
     | Helpers
     |=====================*/
    public function getHorasAttribute(): float
    {
        return round(($this->minutos ?? 0) / 60, 2);
    }
}
