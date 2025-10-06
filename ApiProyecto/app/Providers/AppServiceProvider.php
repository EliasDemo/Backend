<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            // 🔹 Spatie Permission necesita esto para model_has_roles / model_has_permissions
            'user'       => \App\Models\User::class,

            // 🔹 Tus alias de dominio
            'ep_sede'    => \App\Models\EpSede::class,
            'sede'       => \App\Models\Sede::class,
            'facultad'   => \App\Models\Facultad::class,

            // (si usas en otros lados)
            'vm_proceso' => \App\Models\VmProceso::class,
            'vm_evento'  => \App\Models\VmEvento::class,
        ]);
    }
}
