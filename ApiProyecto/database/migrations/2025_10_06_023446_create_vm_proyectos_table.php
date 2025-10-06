<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vm_proyectos', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FKs
            $table->unsignedBigInteger('ep_sede_id');
            $table->foreign('ep_sede_id')
                ->references('id')->on('ep_sede')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unsignedBigInteger('periodo_id');
            $table->foreign('periodo_id')
                ->references('id')->on('periodos_academicos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Datos principales
            $table->string('codigo')->unique();     // UK global
            $table->string('titulo');
            $table->text('descripcion');

            // Catálogos (ajústalos si usas otros valores)
            $table->enum('tipo', ['PROYECTO', 'SERVICIO', 'INVESTIGACION', 'OTRO'])->default('PROYECTO');
            $table->enum('modalidad', ['PRESENCIAL', 'VIRTUAL', 'MIXTA'])->default('PRESENCIAL');
            $table->enum('estado', ['PLANIFICADO', 'EN_CURSO', 'CERRADO', 'CANCELADO'])->default('PLANIFICADO');

            // Horas
            $table->unsignedSmallInteger('horas_planificadas');
            $table->unsignedSmallInteger('horas_minimas_participante')->nullable();

            // Índices de apoyo
            $table->index('ep_sede_id');
            $table->index('periodo_id');
            $table->index('estado');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vm_proyectos');
    }
};
