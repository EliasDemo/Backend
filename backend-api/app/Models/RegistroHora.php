<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * @mixin \Eloquent
 *
 * @property int $estudiante_id
 * @property int $ep_sede_id
 * @property int|null $sede_id
 * @property int $periodo_id
 * @property \Carbon\Carbon $fecha
 * @property int $minutos
 * @property string $actividad
 * @property string|null $detalle
 * @property string|null $evidencia_url
 * @property string $estado
 */
class RegistroHora extends Model
{
    use HasFactory;

    protected $table = 'registro_horas';

    protected $fillable = [
        'estudiante_id',
        'ep_sede_id',
        'sede_id',
        'periodo_id',
        'fecha',
        'minutos',
        'actividad',
        'detalle',
        'evidencia_url',
        'estado',
    ];

    protected $casts = [
        'fecha'   => 'date',
        'minutos' => 'integer',
    ];

    /* ======================
       Relaciones
       ====================== */
    public function estudiante() { return $this->belongsTo(Estudiante::class); }
    public function epSede()     { return $this->belongsTo(EpSede::class, 'ep_sede_id'); }
    public function sede()       { return $this->belongsTo(Sede::class); }
    public function periodo()    { return $this->belongsTo(PeriodoAcademico::class, 'periodo_id'); }

    /* ======================
       Normalización / hooks
       ====================== */
    protected static function booted(): void
    {
        static::saving(function (self $r) {
            if (isset($r->actividad)) {
                $r->actividad = Str::of($r->actividad)->trim()->substr(0, 200);
            }
            // Deriva sede_id desde ep_sede si viene null y la relación existe
            if (empty($r->sede_id) && $r->ep_sede_id) {
                if ($r->relationLoaded('epSede') && $r->epSede) {
                    $r->sede_id = $r->epSede->sede_id;
                }
            }
            // Si no envían periodo_id, usa el periodo actual (si existe)
            if (empty($r->periodo_id)) {
                $r->periodo_id = PeriodoAcademico::where('es_actual', true)->value('id');
            }
        });
    }

    /* ======================
       Scopes
       ====================== */
    public function scopeDeEstudiante($q, int $estudianteId) { return $q->where('estudiante_id', $estudianteId); }
    public function scopeDeEpSede($q, int $epSedeId)         { return $q->where('ep_sede_id', $epSedeId); }
    public function scopeDeSede($q, int $sedeId)             { return $q->where('sede_id', $sedeId); }
    public function scopeDePeriodo($q, int $periodoId)       { return $q->where('periodo_id', $periodoId); }
    public function scopeEstado($q, string $estado)          { return $q->where('estado', Str::upper(trim($estado))); }

    /* ======================
       Helpers de estado
       ====================== */
    public function puedeEditarComoEstudiante(): bool
    {
        return in_array($this->estado, ['BORRADOR','OBSERVADO'], true);
    }

    public function marcarEnviado(): void  { $this->update(['estado' => 'ENVIADO']); }
    public function marcarAprobado(): void { $this->update(['estado' => 'APROBADO']); }
    public function marcarRechazado(): void{ $this->update(['estado' => 'RECHAZADO']); }
    public function marcarObservado(): void{ $this->update(['estado' => 'OBSERVADO']); }
}
