<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Matricula extends Model
{
    use HasFactory;

    protected $table = 'matriculas';

    protected $fillable = [
        'estudiante_id','periodo_id',
        'ciclo','grupo','modalidad_estudio','modo_contrato','fecha_matricula',
    ];

    protected $casts = [
        'fecha_matricula' => 'date',
    ];

    public function estudiante(): BelongsTo { return $this->belongsTo(Estudiante::class, 'estudiante_id'); }
    public function periodo(): BelongsTo { return $this->belongsTo(PeriodoAcademico::class, 'periodo_id'); }
}
