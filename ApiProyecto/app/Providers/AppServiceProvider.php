<?php

namespace App\Providers;

use App\Models\EpSede;
use App\Models\Facultad;
use App\Models\Sede;
use App\Models\VmEvento;
use App\Models\VmProceso;
use App\Models\VmProyecto;
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



            // Para tus relaciones polimórficas
            'vm_proyecto' => VmProyecto::class,
            'vm_proceso'  => VmProceso::class,
            'vm_evento'   => VmEvento::class,

            // Solo si realmente aparecen como type en alguna relación polimórfica tuya
            'ep_sede'     => EpSede::class,
            'sede'        => Sede::class,
            'facultad'    => Facultad::class,
        ]);
    }
}
