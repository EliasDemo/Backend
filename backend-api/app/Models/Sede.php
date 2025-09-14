<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin \Eloquent
 */
class Sede extends Model {
    use HasFactory;

    protected $fillable = [
        'universidad_id',
        'nombre',
        'es_principal',
        'esta_suspendida',
        'suspendida_desde',
    ];

    protected $casts = [
        'es_principal'     => 'boolean',
        'esta_suspendida'  => 'boolean',
        'suspendida_desde' => 'datetime',
    ];

    public function universidad(){ return $this->belongsTo(Universidad::class); }
    public function epOfertadas()
    {
        return $this->belongsToMany(EscuelaProfesional::class, 'ep_sede')
            ->withPivot(['id','vigente_desde','vigente_hasta'])
            ->withTimestamps();
    }
    // Scopes de conveniencia
    public function scopeActivas($q){ return $q->where('esta_suspendida', false); }
    public function scopeSuspendidas($q){ return $q->where('esta_suspendida', true); }

    // Helpers (por si quieres usarlos en controladores/servicios)
    public function suspender(): void
    {
        $this->update([
            'esta_suspendida'  => true,
            'suspendida_desde' => now(),
        ]);
    }

    public function restaurar(): void
    {
        $this->update([
            'esta_suspendida'  => false,
            'suspendida_desde' => null,
        ]);
    }


}
