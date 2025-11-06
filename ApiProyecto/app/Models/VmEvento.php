<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class VmEvento extends Model
{
    use HasFactory;

    protected $table = 'vm_eventos';

    /**
     * Alias ↔ Clase para el target polimórfico.
     * Ajusta estos nombres a tus modelos reales si difieren.
     */
    public const TARGET_ALIAS_TO_CLASS = [
        'ep_sede'  => \App\Models\EpSede::class,
        'sede'     => \App\Models\Sede::class,
        'facultad' => \App\Models\Facultad::class,
    ];

    public const TARGET_CLASS_TO_ALIAS = [
        \App\Models\EpSede::class  => 'ep_sede',
        \App\Models\Sede::class    => 'sede',
        \App\Models\Facultad::class=> 'facultad',
    ];

    protected $fillable = [
        'periodo_id',

        // Acepta ambos para facilidad de uso en controladores:
        'targetable_id',
        'targetable_type',
        'target_id',      // virtual → mutator lo mapea a targetable_id
        'target_type',    // virtual → mutator lo mapea a targetable_type

        'codigo',
        'titulo',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'requiere_inscripcion',
        'cupo_maximo',
    ];

    protected $casts = [
        // Garantiza serialización como YYYY-MM-DD:
        'fecha'                => 'date:Y-m-d',
        'requiere_inscripcion' => 'boolean',
        'cupo_maximo'          => 'integer',
    ];

    /**
     * Al serializar a JSON, expón target_id / target_type (alias) y
     * oculta los campos internos de la relación polimórfica.
     */
    protected $appends = ['target_id', 'target_type'];
    protected $hidden  = ['targetable_id', 'targetable_type'];

    /* =====================
     | Accessors / Mutators
     |=====================*/

    /**
     * target_type (alias) ↔ targetable_type (clase)
     */
    protected function targetType(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $class = $attributes['targetable_type'] ?? null;
                return self::TARGET_CLASS_TO_ALIAS[$class] ?? $class;
            },
            set: function ($value) {
                // Permite enviar alias ('ep_sede', 'sede', 'facultad') o FQCN
                $class = self::TARGET_ALIAS_TO_CLASS[$value] ?? $value;
                return ['targetable_type' => $class];
            }
        );
    }

    /**
     * target_id (alias) ↔ targetable_id
     */
    protected function targetId(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $attributes['targetable_id'] ?? null,
            set: fn ($value) => ['targetable_id' => $value]
        );
    }

    /**
     * (Opcional) Accesores de conveniencia: inicio_at / fin_at como Carbon.
     * No se guardan en BD; útiles para cálculos (p.ej., duración, ventanas).
     * No se incluyen en $appends para no inflar el payload.
     */
    protected function inicioAt(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->fecha || !$this->hora_inicio) return null;
                /** @var Carbon $f */
                $f = $this->fecha instanceof Carbon ? $this->fecha->copy() : Carbon::parse($this->fecha);
                return $f->setTimeFromTimeString($this->hora_inicio);
            }
        );
    }

    protected function finAt(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->fecha || !$this->hora_fin) return null;
                /** @var Carbon $inicio */
                $inicio = $this->inicio_at;
                if (!$inicio) return null;

                $fin = ($this->fecha instanceof Carbon ? $this->fecha->copy() : Carbon::parse($this->fecha))
                    ->setTimeFromTimeString($this->hora_fin);

                // Si cruza medianoche, mover fin al día siguiente
                if ($fin->lessThan($inicio)) {
                    $fin->addDay();
                }
                return $fin;
            }
        );
    }

    /* =====================
     | Relaciones
     |=====================*/
    public function periodo()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_id');
    }

    // Alcance polimórfico (Sede/Facultad/EpSede)
    public function targetable()
    {
        return $this->morphTo();
    }

    // Sesiones polimórficas (el evento "tiene" sesiones)
    public function sesiones()
    {
        return $this->morphMany(VmSesion::class, 'sessionable');
    }

    // Participaciones polimórficas
    public function participaciones()
    {
        return $this->morphMany(VmParticipacion::class, 'participable');
    }

    // Certificados polimórficos
    public function certificados()
    {
        return $this->morphMany(Certificado::class, 'certificable');
    }

    // Imágenes polimórficas
    public function imagenes()
    {
        return $this->morphMany(Imagen::class, 'imageable');
    }

    // Registro de horas (si lo vinculas como 'vinculable' a un evento)
    public function registrosHoras()
    {
        return $this->morphMany(RegistroHora::class, 'vinculable');
    }

    /* =====================
     | Scopes útiles
     |=====================*/
    public function scopeEnCurso($q)              { return $q->where('estado', 'EN_CURSO'); }
    public function scopePlanificados($q)         { return $q->where('estado', 'PLANIFICADO'); }
    public function scopeDelPeriodo($q, int $id)  { return $q->where('periodo_id', $id); }
    public function scopeEnFecha($q, $fecha)      { return $q->whereDate('fecha', $fecha); }

    /**
     * (Opcional) Scopes extra si te sirven:
     */
    public function scopeDesde($q, $fecha)        { return $q->whereDate('fecha', '>=', $fecha); }
    public function scopeHasta($q, $fecha)        { return $q->whereDate('fecha', '<=', $fecha); }
}
