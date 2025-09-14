<?php

namespace App\Providers;

use App\Models\RegistroHora;
use App\Policies\RegistroHoraPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mapeo de Policies para modelos.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        RegistroHora::class => RegistroHoraPolicy::class,
        // Agrega aquí otras policies cuando las vayas creando...
        // Ej: Estudiante::class => EstudiantePolicy::class,
    ];

    /**
     * Registra servicios de autenticación/autorización.
     */
    public function boot(): void
    {
        // Registra el mapeo de policies
        $this->registerPolicies();

        // Super-admin: permite todo al rol "Administrador"
        Gate::before(function ($user, string $ability) {
            return $user->hasRole('Administrador') ? true : null;
        });
    }
}
