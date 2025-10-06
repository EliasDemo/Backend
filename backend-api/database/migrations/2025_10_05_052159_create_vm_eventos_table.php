<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('vm_eventos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ep_sede_id')->constrained('ep_sede')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos_academicos')->restrictOnDelete();
            $table->foreignId('proyecto_id')->nullable()->constrained('vm_proyectos')->nullOnDelete();

            $table->string('codigo', 32)->unique(); // EVT-2024-0001
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();

            $table->enum('tipo_evento', ['FEM','EXTENSION_UNIVERSITARIA','PROYECCION_SOCIAL','OTRO'])->default('FEM');

            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->unsignedInteger('minutos_duracion')
                  ->storedAs("TIMESTAMPDIFF(MINUTE, CONCAT(fecha,' ',hora_inicio), CONCAT(fecha,' ',hora_fin))");

            $table->decimal('latitud', 10,7)->nullable();
            $table->decimal('longitud', 10,7)->nullable();
            $table->unsignedInteger('geofence_radio_m')->nullable();
            $table->boolean('requiere_gps')->default(false);

            $table->unsignedInteger('cupo_maximo')->nullable();
            $table->boolean('requiere_inscripcion')->default(false);

            $table->enum('estado', ['BORRADOR','PUBLICADO','EN_CURSO','CERRADO','CANCELADO'])->default('BORRADOR');

            $table->timestamps();

            $table->index(['ep_sede_id','periodo_id','estado','fecha'], 'idx_vm_evt_scope_fecha');
        });

        DB::statement("
            ALTER TABLE vm_eventos
            ADD CONSTRAINT chk_vm_evt_horas
            CHECK (hora_fin > hora_inicio)
        ");
    }

    public function down(): void {
        Schema::dropIfExists('vm_eventos');
    }
};
