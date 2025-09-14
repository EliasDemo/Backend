<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @mixin \Eloquent
 *
 * @property int         $sede_id
 * @property int         $periodo_id
 * @property int         $persona_id
 * @property string      $cargo
 * @property bool        $activo
 * @property-read string|null $sede_nombre
 * @property-read string|null $persona_nombre
 */
class EncargadoSede extends Model
{
    use HasFactory;

    protected $table = 'encargados_sede';

    protected $fillable = [
        'sede_id',
        'periodo_id',
        'persona_id',
        'cargo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /* ======================
       Relaciones
       ====================== */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    // Atajos (si precargas con with(['sede','persona','periodo']))
    public function getSedeNombreAttribute(): ?string
    {
        return $this->sede?->nombre;
    }

    public function getPersonaNombreAttribute(): ?string
    {
        return $this->persona ? ($this->persona->apellidos.' '.$this->persona->nombres) : null;
    }

    /* ======================
       Normalización / hooks
       ====================== */
    protected static function booted(): void
    {
        static::saving(function (self $e) {
            if (isset($e->cargo)) {
                $e->cargo = Str::upper(trim($e->cargo));
            }
        });
    }

    /* ======================
       Scopes
       ====================== */

    /** @return Builder<static> */
    public function scopeActivos(Builder $q): Builder
    {
        return $q->where('activo', true);
    }

    /** @return Builder<static> */
    public function scopeDePeriodo(Builder $q, int $pid): Builder
    {
        return $q->where('periodo_id', $pid);
    }

    /** @return Builder<static> */
    public function scopeDeSede(Builder $q, int $sid): Builder
    {
        return $q->where('sede_id', $sid);
    }

    /** @return Builder<static> */
    public function scopeDelPeriodoActual(Builder $q): Builder
    {
        return $q->whereHas('periodo', fn (Builder $qq) => $qq->actual());
    }
}
