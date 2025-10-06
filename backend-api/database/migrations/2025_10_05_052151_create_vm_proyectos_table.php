<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('vm_proyectos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ep_sede_id')->constrained('ep_sede')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos_academicos')->restrictOnDelete();

            $table->string('codigo', 32)->unique(); // ej: PROY-2024-0001
            $table->string('titulo', 180);
            $table->text('descripcion')->nullable();

            $table->enum('tipo', ['PROYECCION_SOCIAL','EXTENSION_UNIVERSITARIA','RESPONSABILIDAD_SOCIAL','OTRO'])
                  ->default('PROYECCION_SOCIAL');
            $table->enum('modalidad', ['PRESENCIAL','VIRTUAL','MIXTA'])->default('PRESENCIAL');

            $table->enum('estado', ['BORRADOR','PUBLICADO','EN_EJECUCION','CERRADO','CANCELADO'])
                  ->default('BORRADOR');
            $table->unsignedSmallInteger('horas_planificadas')->default(0);
            $table->unsignedSmallInteger('horas_minimas_participante')->nullable();

            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();

            $table->string('lugar', 200)->nullable();
            $table->decimal('latitud', 10,7)->nullable();
            $table->decimal('longitud', 10,7)->nullable();
            $table->unsignedInteger('geofence_radio_m')->nullable();
            $table->string('entidad_aliada', 180)->nullable();
            $table->string('contacto_externo', 120)->nullable();
            $table->string('telefono_externo', 50)->nullable();
            $table->string('email_externo', 120)->nullable();

            $table->foreignId('responsable_persona_id')->nullable()->constrained('personas')->restrictOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['ep_sede_id','periodo_id','estado'], 'idx_vm_proy_scope');
        });

        DB::statement("
            ALTER TABLE vm_proyectos
            ADD CONSTRAINT chk_vm_proy_fechas
            CHECK (fecha_fin IS NULL OR fecha_inicio IS NULL OR fecha_fin >= fecha_inicio)
        ");
    }

    public function down(): void {
        Schema::dropIfExists('vm_proyectos');
    }
};
