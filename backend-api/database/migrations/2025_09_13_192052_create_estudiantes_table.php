<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();

            // Persona base (borra el estudiante si se borra la persona)
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();

            // Identificación académica
            $table->string('codigo', 20)->unique(); // código UPeU visible
            $table->foreignId('ep_sede_id')->constrained('ep_sede')->restrictOnDelete();

            // Periodo de ingreso (si se borra el periodo, no rompemos la fila)
            $table->foreignId('ingreso_periodo_id')->nullable()
                  ->constrained('periodos_academicos')->nullOnDelete();

            // Desnormalizado para filtros rápidos (sin JOIN)
            $table->string('cohorte_codigo', 16)->nullable()->index(); // ej. 2025-1

            // Estado académico mínimo
            $table->enum('estado', ['ACTIVO','SUSPENDIDO','RESERVA','RETIRADO','EGRESADO','TRASLADADO'])
                  ->default('ACTIVO');

            $table->unsignedTinyInteger('ciclo_actual')->nullable(); // 1..12 aprox

            $table->timestamps();

            // Evita duplicados para la misma persona dentro de la misma oferta EP–Sede
            $table->unique(['persona_id','ep_sede_id'], 'ux_estudiante_persona_epsede');

            // Índices para consultas usuales
            $table->index(['ep_sede_id','estado'], 'idx_epsede_estado');
            $table->index(['ingreso_periodo_id'], 'idx_ingreso_periodo');
        });

        // Reglas adicionales (MySQL 8+: si tu versión las ignora, no pasa nada)
        DB::statement("
            ALTER TABLE estudiantes
            ADD CONSTRAINT chk_estudiantes_ciclo
            CHECK (ciclo_actual IS NULL OR (ciclo_actual BETWEEN 1 AND 12))
        ");
    }

    public function down(): void {
        Schema::dropIfExists('estudiantes');
    }
};
