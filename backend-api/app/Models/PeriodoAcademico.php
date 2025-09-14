<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * @property int $anio
 * @property int $ciclo
 * @property string $codigo
 * @property Carbon $fecha_inicio
 * @property Carbon $fecha_fin
 * @property bool $es_actual
 * @property string $estado
 */
class PeriodoAcademico extends Model
{
    use HasFactory;

    protected $table = 'periodos_academicos';

    protected $fillable = [
        'codigo',
        'anio',
        'ciclo',
        'estado',
        'es_actual',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'es_actual'    => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $p) {
            // Normaliza codigo (e.g., 2025/1 -> 2025-1)
            if (isset($p->codigo) && $p->codigo !== null) {
                $p->codigo = strtoupper(trim($p->codigo));
                $p->codigo = preg_replace('#^(\d{4})[\/\-](\d+)$#', '$1-$2', $p->codigo);
            }

            // Completa anio/ciclo desde codigo si faltan
            if ($p->codigo && (!$p->anio || !$p->ciclo)) {
                if (preg_match('#^(\d{4})-(\d+)$#', $p->codigo, $m)) {
                    $p->anio  = (int) $m[1];
                    $p->ciclo = (int) $m[2];
                }
            }

            // O genera codigo desde anio/ciclo si falta
            if (!$p->codigo && $p->anio && $p->ciclo) {
                $p->codigo = sprintf('%04d-%d', $p->anio, $p->ciclo);
            }
        });
    }

    /* Scopes */
    public function scopeAnio($q, int $anio)         { return $q->where('anio', $anio); }
    public function scopeCiclo($q, int $ciclo)       { return $q->where('ciclo', $ciclo); }
    public function scopeCodigo($q, string $codigo)  { return $q->where('codigo', strtoupper(trim($codigo))); }

    public function scopeVigentesEn($q, ?string $fecha = null)
    {
        $f = $fecha ? Carbon::parse($fecha)->toDateString() : now()->toDateString();
        return $q->where('fecha_inicio', '<=', $f)->where('fecha_fin', '>=', $f);
    }

    public function scopeEnCurso($q)      { return $q->where('estado', 'EN_CURSO'); }
    public function scopePlanificados($q) { return $q->where('estado', 'PLANIFICADO'); }
    public function scopeCerrados($q)     { return $q->where('estado', 'CERRADO'); }
    public function scopeActual($q)       { return $q->where('es_actual', true); }

    /* Helpers */
    public function contieneFecha(?string $fecha = null): bool
    {
        $f = $fecha ? Carbon::parse($fecha)->toDateString() : now()->toDateString();
        return $this->fecha_inicio->toDateString() <= $f && $this->fecha_fin->toDateString() >= $f;
    }

    public function enCurso(): bool     { return $this->estado === 'EN_CURSO'; }
    public function planificado(): bool { return $this->estado === 'PLANIFICADO'; }
    public function cerrado(): bool     { return $this->estado === 'CERRADO'; }

    public function etiqueta(): string  { return $this->codigo; }

    public static function rangosSeSolapan(Carbon $a, Carbon $b, Carbon $c, Carbon $d): bool
    {
        return $a->toDateString() <= $d->toDateString()
            && $c->toDateString() <= $b->toDateString();
    }
}
