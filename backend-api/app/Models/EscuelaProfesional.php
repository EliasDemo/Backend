<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @mixin \Eloquent
 *
 * @property int         $id
 * @property int         $facultad_id
 * @property string      $codigo
 * @property string      $nombre
 * @property string|null $slug
 * @property bool        $esta_suspendida
 * @property \Carbon\CarbonImmutable|\Carbon\Carbon|null $suspendida_desde
 */
class EscuelaProfesional extends Model
{
    use HasFactory;

    protected $table = 'escuelas_profesionales';

    protected $fillable = [
        'facultad_id',
        'codigo',
        'nombre',
        'slug',
        'esta_suspendida',
        'suspendida_desde',
    ];

    protected $casts = [
        'esta_suspendida'  => 'boolean',
        'suspendida_desde' => 'datetime',
    ];

    /* ======================
       Relaciones
       ====================== */

    public function facultad(): BelongsTo
    {
        return $this->belongsTo(Facultad::class);
    }

    /** Sedes donde se oferta (vía tabla pivote ep_sede) */
    public function sedes(): BelongsToMany
    {
        return $this->belongsToMany(Sede::class, 'ep_sede')
            ->withPivot(['id', 'vigente_desde', 'vigente_hasta'])
            ->withTimestamps();
    }

    /** Fila(s) ep_sede relacionadas (atajo útil) */
    public function epSedes(): HasMany
    {
        return $this->hasMany(EpSede::class, 'escuela_profesional_id');
    }

    /* ======================
       Hooks: normalización + slug único por facultad
       ====================== */

    protected static function booted(): void
    {
        static::saving(function (self $ep) {
            if ($ep->isDirty('codigo') && isset($ep->codigo)) {
                $ep->codigo = Str::upper(trim($ep->codigo));
            }
            if ($ep->isDirty('nombre') && isset($ep->nombre)) {
                $ep->nombre = trim($ep->nombre);
            }

            // Si no hay slug pero sí nombre y facultad, lo generamos.
            if (empty($ep->slug) && !empty($ep->nombre) && !empty($ep->facultad_id)) {
                $ep->slug = static::uniqueSlugForFaculty($ep->nombre, (int) $ep->facultad_id, $ep->id, false);
            }
            // Si editaron manualmente el slug, lo normalizamos y garantizamos unicidad.
            elseif ($ep->isDirty('slug') && !empty($ep->slug) && !empty($ep->facultad_id)) {
                $ep->slug = static::uniqueSlugForFaculty($ep->slug, (int) $ep->facultad_id, $ep->id, true);
            }
        });
    }

    /**
     * Genera un slug único dentro de la misma facultad.
     *
     * @param  string      $base       Texto base (nombre o slug ingresado)
     * @param  int|null    $facultadId ID de facultad (si null/no definido, sólo normaliza)
     * @param  int|null    $ignoreId   ID a ignorar (para updates)
     * @param  bool        $isRaw      true si $base ya es un slug “manual”; de todos modos se normaliza
     */
    protected static function uniqueSlugForFaculty(string $base, ?int $facultadId, ?int $ignoreId = null, bool $isRaw = false): string
    {
        $slugBase = $isRaw ? trim($base) : Str::slug($base);
        // Asegura que el slug no quede vacío
        $slugBase = $slugBase !== '' ? $slugBase : 'ep';

        // Si no tenemos facultad, no podemos verificar unicidad por ámbito; devolvemos normalizado.
        if (!$facultadId) {
            return $slugBase;
        }

        $slug = $slugBase;
        $i    = 2;

        while (
            static::query()
                ->where('facultad_id', $facultadId)
                ->where('slug', $slug)
                ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$slugBase}-{$i}";
            $i++;
        }

        return $slug;
    }

    /* ======================
       Scopes
       ====================== */

    /** @return Builder<static> */
    public function scopeDeFacultad(Builder $q, int $facultadId): Builder
    {
        return $q->where('facultad_id', $facultadId);
    }

    /** @return Builder<static> */
    public function scopeActivas(Builder $q): Builder
    {
        return $q->where('esta_suspendida', false);
    }

    /** @return Builder<static> */
    public function scopeSuspendidas(Builder $q): Builder
    {
        return $q->where('esta_suspendida', true);
    }

    /** @return Builder<static> */
    public function scopeCodigo(Builder $q, string $codigo): Builder
    {
        return $q->where('codigo', Str::upper(trim($codigo)));
    }

    /* ======================
       Helpers estado
       ====================== */

    public function suspender(): void
    {
        if (!$this->esta_suspendida) {
            $this->update([
                'esta_suspendida'  => true,
                'suspendida_desde' => now(),
            ]);
        }
    }

    public function restaurar(): void
    {
        if ($this->esta_suspendida) {
            $this->update([
                'esta_suspendida'  => false,
                'suspendida_desde' => null,
            ]);
        }
    }
}
