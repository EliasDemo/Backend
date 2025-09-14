<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * @mixin \Eloquent
 */
class Facultad extends Model
{
    use HasFactory;

    // Importante porque Eloquent pluraliza en inglés ("facultads")
    protected $table = 'facultades';

    protected $fillable = [
        'universidad_id',
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

    /* ===========================
     | Relaciones
     * =========================== */
    public function universidad() { return $this->belongsTo(Universidad::class); }
    public function escuelas()    { return $this->hasMany(EscuelaProfesional::class); }

    /* ===========================
     | Hooks: normalización + slug único por universidad
     * =========================== */
    protected static function booted(): void
    {
        static::saving(function (Facultad $fac) {
            // Normaliza código y nombre
            if ($fac->isDirty('codigo') && isset($fac->codigo)) {
                $fac->codigo = strtoupper(trim($fac->codigo));
            }
            if ($fac->isDirty('nombre') && isset($fac->nombre)) {
                $fac->nombre = trim($fac->nombre);
            }

            // Genera/asegura slug único por universidad
            if (empty($fac->slug) && ! empty($fac->nombre)) {
                $fac->slug = static::uniqueSlugForUniversity($fac->nombre, (int) $fac->universidad_id, $fac->id);
            } elseif ($fac->isDirty('slug') && ! empty($fac->slug)) {
                $fac->slug = static::uniqueSlugForUniversity($fac->slug, (int) $fac->universidad_id, $fac->id, $isRaw=true);
            }
        });
    }

    /** Crea un slug único dentro de la misma universidad (agrega -2, -3, … si colisiona). */
    protected static function uniqueSlugForUniversity(string $base, int $universidadId, ?int $ignoreId = null, bool $isRaw = false): string
    {
        $slugBase = $isRaw ? Str::slug($base) : Str::slug($base);
        $slug = $slugBase;
        $i = 2;

        while (static::query()
            ->where('universidad_id', $universidadId)
            ->where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = "{$slugBase}-{$i}";
            $i++;
        }

        return $slug;
    }

    /* ===========================
     | Scopes
     * =========================== */
    public function scopeDeUniversidad($q, int $universidadId) {
        return $q->where('universidad_id', $universidadId);
    }
    public function scopeActivas($q)     { return $q->where('esta_suspendida', false); }
    public function scopeSuspendidas($q) { return $q->where('esta_suspendida', true); }
    public function scopeCodigo($q, string $codigo) { return $q->where('codigo', strtoupper(trim($codigo))); }
    public function scopeSlug($q, string $slug)     { return $q->where('slug', Str::slug($slug)); }

    /* ===========================
     | Helpers de estado
     * =========================== */
    public function suspender(): void
    {
        if (! $this->esta_suspendida) {
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
