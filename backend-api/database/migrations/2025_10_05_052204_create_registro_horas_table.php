<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_horas', function (Blueprint $table) {
            $table->id();

            // FKs principales
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('ep_sede_id')->constrained('ep_sede')->cascadeOnDelete(); // usa 'ep_sedes' si tu tabla es plural
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos_academicos')->restrictOnDelete();

            // Datos del registro
            $table->date('fecha');
            $table->unsignedSmallInteger('minutos');
            $table->string('actividad', 200);
            $table->string('detalle')->nullable();   // usa ->text() si necesitas >255 chars
            $table->string('evidencia_url')->nullable();
            $table->string('estado', 20)->default('BORRADOR');
            $table->string('origen', 50)->nullable();

            // Relación polimórfica (VM)
            $table->nullableMorphs('vinculable'); // crea vinculable_type (index) y vinculable_id (nullable)

            // Campos de relación VM directos (SIN FK aquí para no depender del orden de migraciones)
            $table->unsignedBigInteger('sesion_id')->nullable();
            $table->unsignedBigInteger('asistencia_id')->nullable();

            $table->timestamps();

            // Índices útiles
            $table->index(['estudiante_id', 'periodo_id', 'fecha']);
            $table->index('estado');
            // Nota: nullableMorphs ya crea índices sobre (vinculable_type, vincululable_id)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_horas');
    }
};
