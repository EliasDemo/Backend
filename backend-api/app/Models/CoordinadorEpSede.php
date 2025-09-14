<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class CoordinadorEpSede extends Model
{
    use HasFactory;

    protected $table = 'coordinadores_ep_sede';

    protected $fillable = [
        'ep_sede_id',
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
    public function persona() { return $this->belongsTo(Persona::class); }
    public function epSede()  { return $this->belongsTo(EpSede::class, 'ep_sede_id'); }
    public function periodo() { return $this->belongsTo(PeriodoAcademico::class, 'periodo_id'); }

    // Atajos (útiles si precargas con with(['epSede.sede','epSede.ep','persona','periodo']))
    public function getSedeNombreAttribute(): ?string
    {
        return $this->epSede?->sede?->nombre;
    }
    public function getEpNombreAttribute(): ?string
    {
        return $this->epSede?->ep?->nombre;
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
        static::saving(function (self $c) {
            if (isset($c->cargo)) {
                $c->cargo = Str::upper(trim($c->cargo));
            }
        });
    }

    /* ======================
       Scopes
       ====================== */
    public function scopeActivos($q)                  { return $q->where('activo', true); }
    public function scopeDePeriodo($q, int $periodoId){ return $q->where('periodo_id', $periodoId); }
    public function scopeDeEp($q, int $epId)
    {
        return $q->whereHas('epSede', fn($qq) => $qq->where('escuela_profesional_id', $epId));
    }
    public function scopeDeSede($q, int $sedeId)
    {
        return $q->whereHas('epSede', fn($qq) => $qq->where('sede_id', $sedeId));
    }
    public function scopeDelPeriodoActual($q)
    {
        return $q->whereHas('periodo', fn($qq) => $qq->actual());
    }
}
