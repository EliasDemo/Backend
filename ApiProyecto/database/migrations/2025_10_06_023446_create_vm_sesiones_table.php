<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vm_sesiones', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Alcance polimórfico (puede pertenecer a VmProceso o VmEvento)
            $table->unsignedBigInteger('sessionable_id');
            $table->string('sessionable_type');

            // Programación
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            // Estado
            $table->enum('estado', ['PLANIFICADO', 'EN_CURSO', 'CERRADO', 'CANCELADO'])
                  ->default('PLANIFICADO');

            // Índices
            $table->index(['sessionable_type', 'sessionable_id']);
            $table->index('fecha');
            $table->index('estado');

            $table->timestamps();
        });

        // (Opcional) CHECKs si tu motor lo soporta (MySQL 8+/PostgreSQL)
        // DB::statement("ALTER TABLE vm_sesiones ADD CONSTRAINT chk_horas CHECK (hora_inicio <= hora_fin)");
    }

    public function down(): void
    {
        Schema::dropIfExists('vm_sesiones');
    }
};
