<?php

namespace App\Models;

use App\Casts\LowercaseCast;
use App\Casts\TrimCast;
use App\Enums\AccountStatus;
use App\Models\Concerns\HasAccountStatus;
use App\Models\Concerns\HasProfilePhoto;
use App\Models\Concerns\HasRecoveryCode;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    use HasAccountStatus, HasProfilePhoto, HasRecoveryCode;
     use HasRoles;

    /**
     * Campos asignables masivamente
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'profile_photo',
        'status',
        'recovery_code',
        'recovery_expires_at',
    ];

    /**
     * Campos ocultos en arrays/JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
        'recovery_code',
    ];

    /**
     * Casts de atributos
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'recovery_expires_at' => 'datetime',
            'password'            => 'hashed',
            'status'              => AccountStatus::class, // enum
            'username'            => TrimCast::class,      // limpia espacios
            'email'               => LowercaseCast::class, // normaliza a minúsculas
        ];
    }

    /**
     * Usado por Laravel para "clave de autenticación" en formularios
     * (Si usas Fortify o Breeze, ajusta el controller/provider para username)
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * Ejemplo de relación con imágenes polimórficas (si la usas más adelante)
     */
    // public function images()
    // {
    //     return $this->morphMany(Image::class, 'imageable');
    // }

    // public function avatar() // morphOne si solo quieres una
    // {
    //     return $this->morphOne(Image::class, 'imageable');
    // }

    /**
     * Accessor opcional para mostrar nombre público
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(function () {
            return $this->name ?: $this->username;
        });
    }

    public function persona()
    {
        return $this->belongsTo(\App\Models\Persona::class);
    }

}
