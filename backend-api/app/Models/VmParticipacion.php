<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{MorphTo, BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VmParticipacion extends Model
{
    use HasFactory;

    protected $table = 'vm_participaciones';

    protected $fillable = [
        'participable_type','participable_id',
        'estudiante_id','persona_id',
        'externo_nombre','externo_documento',
        'rol','estado',
    ];

    public function participable(): MorphTo { return $this->morphTo(); }
    public function estudiante(): BelongsTo { return $this->belongsTo(Estudiante::class); }
    public function persona(): BelongsTo { return $this->belongsTo(Persona::class); }
    public function asistencias(): HasMany { return $this->hasMany(VmAsistencia::class, 'participacion_id'); }
}
