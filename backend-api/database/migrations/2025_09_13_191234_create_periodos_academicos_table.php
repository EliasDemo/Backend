<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('periodos_academicos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 16)->unique();   // ej.: 2025-1
            $table->unsignedSmallInteger('anio');     // 2025
            $table->unsignedTinyInteger('ciclo');     // 1 o 2

            $table->enum('estado', ['PLANIFICADO','EN_CURSO','CERRADO'])->default('PLANIFICADO');
            $table->boolean('es_actual')->default(false);

            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            // MySQL 8+: columna generada
            $table->unsignedInteger('duracion_dias')
                  ->storedAs('DATEDIFF(`fecha_fin`, `fecha_inicio`) + 1');

            $table->timestamps();

            // Unicidad por año-ciclo
            $table->unique(['anio','ciclo'], 'ux_periodo_anio_ciclo');

            // Índices útiles
            $table->index(['estado','es_actual'], 'idx_periodo_estado_actual');
            $table->index(['fecha_inicio','fecha_fin'], 'idx_periodo_fechas');
        });

        // Checks (MySQL 8+; en versiones viejas se ignoran)
        DB::statement("
            ALTER TABLE periodos_academicos
            ADD CONSTRAINT chk_periodo_fechas
            CHECK (fecha_fin >= fecha_inicio)
        ");

        DB::statement("
            ALTER TABLE periodos_academicos
            ADD CONSTRAINT chk_periodo_ciclo
            CHECK (ciclo IN (1,2))
        ");
    }

    public function down(): void {
        Schema::dropIfExists('periodos_academicos');
    }
};
