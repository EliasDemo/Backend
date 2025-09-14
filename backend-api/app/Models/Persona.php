<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * @mixin \Eloquent
 * @property string|null $dni
 * @property string $apellidos
 * @property string $nombres
 * @property string|null $email_institucional
 * @property string|null $email_personal
 * @property string|null $celular
 * @property string|null $sexo
 * @property \Carbon\Carbon|null $fecha_nacimiento
 */
class Persona extends Model
{
    use HasFactory;

    protected $fillable = [
        'dni','apellidos','nombres',
        'email_institucional','email_personal','celular',
        'sexo','fecha_nacimiento',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /* ======================
       Normalización
       ====================== */
    protected static function booted(): void
    {
        static::saving(function (self $p) {
            if (isset($p->dni) && $p->dni !== null) {
                $p->dni = trim($p->dni);
            }

            if (isset($p->apellidos)) {
                $p->apellidos = Str::upper(trim($p->apellidos));
            }

            if (isset($p->nombres)) {
                // Title Case con soporte multibyte
                $p->nombres = mb_convert_case(trim($p->nombres), MB_CASE_TITLE, "UTF-8");
            }

            if (isset($p->email_institucional) && $p->email_institucional !== null) {
                $p->email_institucional = Str::lower(trim($p->email_institucional));
            }

            if (isset($p->email_personal) && $p->email_personal !== null) {
                $p->email_personal = Str::lower(trim($p->email_personal));
            }

            if (isset($p->celular) && $p->celular !== null) {
                $soloDigitos = preg_replace('/\D+/', '', $p->celular);
                $p->celular = $soloDigitos ?: null;
            }
        });
    }

    /* ======================
       Relaciones típicas
       ====================== */
    public function estudiante()     { return $this->hasOne(Estudiante::class); }
    public function coordinaciones() { return $this->hasMany(CoordinadorEpSede::class, 'persona_id'); }
    public function encargaturas()   { return $this->hasMany(EncargadoSede::class, 'persona_id'); }
    public function usuario()        { return $this->hasOne(User::class); }

    /* ======================
       Accessors / Helpers
       ====================== */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->apellidos.' '.$this->nombres);
    }

    public function getInicialesAttribute(): string
    {
        // Toma primera letra de cada palabra del nombre
        $parts = preg_split('/\s+/', $this->nombres ?? '');
        $ini = '';
        foreach ($parts as $p) {
            $ini .= mb_substr($p, 0, 1, 'UTF-8');
        }
        return Str::upper($ini);
    }

    /* ======================
       Scopes de búsqueda
       ====================== */
    public function scopeDni($q, string $dni)
    {
        return $q->where('dni', trim($dni));
    }

    public function scopeEmail($q, string $email)
    {
        return $q->where(function ($qq) use ($email) {
            $qq->where('email_institucional', Str::lower(trim($email)))
               ->orWhere('email_personal', Str::lower(trim($email)));
        });
    }

    public function scopeBuscar($q, string $term)
    {
        $t = Str::upper(trim($term));
        return $q->where(function ($qq) use ($t) {
            $qq->where('nombre_busqueda', 'like', "%{$t}%")
               ->orWhere('dni', 'like', "%{$t}%");
        });
    }
}
