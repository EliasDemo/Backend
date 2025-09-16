<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * @mixin \Eloquent
 *
 * @property int $persona_id
 * @property string $codigo
 * @property int $ep_sede_id
 * @property int|null $ingreso_periodo_id
 * @property string|null $cohorte_codigo
 * @property string $estado
 * @property int|null $ciclo_actual
 */
class Estudiante extends Model
{
    use HasFactory;

    protected $fillable = [
        'persona_id',
        'codigo',
        'ep_sede_id',
        'ingreso_periodo_id',
        'cohorte_codigo',
        'estado',
        'ciclo_actual',
    ];

    protected $casts = [
        'ciclo_actual' => 'integer',
    ];

    /* ======================
       Relaciones
       ====================== */
    public function persona()        { return $this->belongsTo(Persona::class); }
    public function epSede()         { return $this->belongsTo(EpSede::class, 'ep_sede_id'); }
    public function periodoIngreso() { return $this->belongsTo(PeriodoAcademico::class, 'ingreso_periodo_id'); }

    // (Opcional) helpers por relación ya cargada
    public function getSedeNombreAttribute(): ?string
    {
        return $this->epSede?->sede?->nombre;
    }
    public function getEmailInstitucionalAttribute(): ?string
    {
        return $this->persona?->email_institucional;
    }
    public function getNombreCompletoAttribute(): string
    {
        return $this->persona ? ($this->persona->apellidos.' '.$this->persona->nombres) : '';
    }

    /* ======================
       Normalización / hooks
       ====================== */
    protected static function booted(): void
    {
        static::saving(function (self $e) {
            if (isset($e->codigo)) {
                $e->codigo = Str::upper(trim($e->codigo));
            }

            // Sincroniza cohorte cuando cambia ingreso_periodo_id
            if ($e->isDirty('ingreso_periodo_id')) {
                if ($e->ingreso_periodo_id) {
                    if ($e->relationLoaded('periodoIngreso') && $e->periodoIngreso) {
                        $e->cohorte_codigo = $e->periodoIngreso->codigo;
                    } else {
                        // Busca sólo el código (consulta liviana)
                        $e->cohorte_codigo = PeriodoAcademico::whereKey($e->ingreso_periodo_id)->value('codigo');
                    }
                } else {
                    // Si lo limpian, también limpiamos la cohorte
                    $e->cohorte_codigo = null;
                }
            }
        });
    }

    /* ======================
       Scopes
       ====================== */
    public function scopeCodigo($q, string $codigo)
    {
        return $q->where('codigo', Str::upper(trim($codigo)));
    }
    public function scopeEstado($q, string $estado)
    {
        return $q->where('estado', Str::upper(trim($estado)));
    }
    public function scopeDeEp($q, int $epId)
    {
        return $q->whereHas('epSede', fn($qq) => $qq->where('escuela_profesional_id', $epId));
    }
    public function scopeDeSede($q, int $sedeId)
    {
        return $q->whereHas('epSede', fn($qq) => $qq->where('sede_id', $sedeId));
    }
    public function scopeCohorte($q, string $cohorteCodigo)
    {
        return $q->where('cohorte_codigo', strtoupper(trim($cohorteCodigo)));
    }

    /* ======================
       Helpers
       ====================== */
    public function cambiarEstado(string $nuevo): void
    {
        $nuevo = Str::upper(trim($nuevo));
        if (! in_array($nuevo, ['ACTIVO','SUSPENDIDO','RESERVA','RETIRADO','EGRESADO','TRASLADADO'], true)) {
            throw new \InvalidArgumentException("Estado inválido: {$nuevo}");
        }
        $this->update(['estado' => $nuevo]);
    }

     // Relación con la EscuelaProfesional
    public function escuelaProfesional()
    {
        return $this->epSede->escuelaProfesional; // Accedemos a través de EpSede
    }
}
