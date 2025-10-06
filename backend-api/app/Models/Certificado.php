<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{MorphTo, BelongsTo};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Certificado extends Model
{
    use HasFactory;

    protected $table = 'certificados';

    protected $fillable = [
        'certificable_type','certificable_id',
        'persona_id','estudiante_id','rol','minutos',
        'codigo_unico','estado','emitido_at',
        'archivo_disk','archivo_path','extra',
    ];

    protected $casts = [
        'emitido_at' => 'datetime',
        'extra'      => 'array',
    ];

    public function certificable(): MorphTo { return $this->morphTo(); }
    public function persona(): BelongsTo { return $this->belongsTo(Persona::class, 'persona_id'); }
    public function estudiante(): BelongsTo { return $this->belongsTo(Estudiante::class, 'estudiante_id'); }
}
