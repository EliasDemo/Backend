<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('vm_sesiones', function (Blueprint $table) {
            $table->id();

            $table->morphs('sessionable'); // vm_proyectos o vm_eventos

            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->unsignedInteger('minutos_duracion')
                  ->storedAs("TIMESTAMPDIFF(MINUTE, CONCAT(fecha,' ',hora_inicio), CONCAT(fecha,' ',hora_fin))");

            $table->decimal('latitud', 10,7)->nullable();
            $table->decimal('longitud', 10,7)->nullable();
            $table->unsignedInteger('geofence_radio_m')->nullable();
            $table->boolean('requiere_gps')->default(false);

            $table->string('codigo_manual', 16)->nullable();

            $table->enum('estado', ['PROGRAMADA','ABIERTA','CERRADA','CANCELADA'])->default('PROGRAMADA');

            $table->timestamps();

            $table->index(['sessionable_type','sessionable_id','fecha','estado'], 'idx_vm_sesion_scope');
        });

        DB::statement("
            ALTER TABLE vm_sesiones
            ADD CONSTRAINT chk_vm_ses_horas
            CHECK (hora_fin > hora_inicio)
        ");
    }

    public function down(): void {
        Schema::dropIfExists('vm_sesiones');
    }
};
