<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VmAsistencia extends Model
{
    use HasFactory;

    protected $table = 'vm_asistencias';

    protected $fillable = [
        'sesion_id','estudiante_id','participacion_id','qr_token_id',
        'metodo','check_in_at','check_out_at',
        'latitud','longitud','distancia_m','gps_valido','device_info','ip',
        'estado','minutos_validados',
    ];

    protected $casts = [
        'check_in_at'  => 'datetime',
        'check_out_at' => 'datetime',
        'gps_valido'   => 'bool',
        'latitud'      => 'float',
        'longitud'     => 'float',
    ];

    protected $appends = ['horas_validadas'];

    public function getHorasValidadasAttribute(): float
    {
        return round(($this->minutos_validados ?? 0) / 60, 2);
    }

    public function sesion(): BelongsTo { return $this->belongsTo(VmSesion::class, 'sesion_id'); }
    public function estudiante(): BelongsTo { return $this->belongsTo(Estudiante::class, 'estudiante_id'); }
    public function participacion(): BelongsTo { return $this->belongsTo(VmParticipacion::class, 'participacion_id'); }
    public function qrToken(): BelongsTo { return $this->belongsTo(VmQrToken::class, 'qr_token_id'); }
}
