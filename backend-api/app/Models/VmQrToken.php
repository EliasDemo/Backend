<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class VmQrToken extends Model
{
    use HasFactory;

    protected $table = 'vm_qr_tokens';

    protected $fillable = [
        'sesion_id','token','usable_from','expires_at',
        'max_usos','usos','activo',
        'latitud','longitud','geofence_radio_m','requiere_gps',
        'creado_por',
    ];

    protected $casts = [
        'usable_from'  => 'datetime',
        'expires_at'   => 'datetime',
        'activo'       => 'bool',
        'requiere_gps' => 'bool',
        'latitud'      => 'float',
        'longitud'     => 'float',
    ];

    public function sesion(): BelongsTo { return $this->belongsTo(VmSesion::class, 'sesion_id'); }
    public function creador(): BelongsTo { return $this->belongsTo(User::class, 'creado_por'); }

    public function isUsable(?Carbon $at = null): bool
    {
        $now = $at ?? now();
        if (!$this->activo) return false;
        if ($this->usable_from && $now->lt($this->usable_from)) return false;
        if ($this->expires_at && $now->gt($this->expires_at)) return false;
        if ($this->max_usos !== null && $this->usos >= $this->max_usos) return false;
        return true;
    }
}
