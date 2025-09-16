<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * @property Carbon|null $vigente_desde
 * @property Carbon|null $vigente_hasta
 */
class EpSede extends Model
{
    use HasFactory;

    protected $table = 'ep_sede';

    protected $fillable = [
        'escuela_profesional_id',
        'sede_id',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
    ];

    // Relaciones
    public function ep()   { return $this->belongsTo(EscuelaProfesional::class, 'escuela_profesional_id'); }
    public function sede() { return $this->belongsTo(Sede::class); }

    /* Scopes útiles */
    // Vigentes en una fecha (por defecto hoy)
    public function scopeVigentesEn($q, $fecha = null)
    {
        $f = $fecha ? Carbon::parse($fecha)->toDateString() : now()->toDateString();

        return $q->where(function ($qq) use ($f) {
                $qq->whereNull('vigente_desde')
                   ->orWhere('vigente_desde', '<=', $f);
            })
            ->where(function ($qq) use ($f) {
                $qq->whereNull('vigente_hasta')
                   ->orWhere('vigente_hasta', '>=', $f);
            });
    }

    // Históricas (ya no vigentes hoy)
    public function scopeNoVigentesHoy($q)
    {
        $hoy = now()->toDateString();

        return $q->whereNot(function ($qq) use ($hoy) {
            $qq->where(function ($q1) use ($hoy) {
                    $q1->whereNull('vigente_desde')
                       ->orWhere('vigente_desde', '<=', $hoy);
                })
               ->where(function ($q2) use ($hoy) {
                    $q2->whereNull('vigente_hasta')
                       ->orWhere('vigente_hasta', '>=', $hoy);
               });
        });
    }

    /* Helpers */
    public function esVigenteEn($fecha = null): bool
    {
        $f = $fecha ? Carbon::parse($fecha)->toDateString() : now()->toDateString();

        // Evita falsos positivos del IDE: formatea sólo si es DateTimeInterface
        $desdeStr = $this->vigente_desde instanceof \DateTimeInterface
            ? $this->vigente_desde->format('Y-m-d')
            : null;

        $hastaStr = $this->vigente_hasta instanceof \DateTimeInterface
            ? $this->vigente_hasta->format('Y-m-d')
            : null;

        $desdeOk = $desdeStr === null || $desdeStr <= $f;
        $hastaOk = $hastaStr === null || $hastaStr >= $f;

        return $desdeOk && $hastaOk;
    }

     public function escuelaProfesional()
    {
        return $this->belongsTo(EscuelaProfesional::class, 'escuela_profesional_id');
    }
}
