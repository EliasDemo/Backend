<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos_academicos')->cascadeOnDelete();

            $table->unsignedTinyInteger('ciclo')->nullable();
            $table->string('grupo', 20)->nullable();
            $table->enum('modalidad_estudio', ['PRESENCIAL','VIRTUAL','MIXTA'])->nullable();
            $table->enum('modo_contrato', ['ORDINARIO','EXTRAORDINARIO','CONVALIDACION','INTERCAMBIO','OTRO'])->nullable();
            $table->date('fecha_matricula')->nullable();

            $table->timestamps();

            $table->unique(['estudiante_id','periodo_id'], 'ux_matricula_est_periodo');
            $table->index(['periodo_id','ciclo','grupo'], 'idx_matricula_filtros');
        });
    }

    public function down(): void {
        Schema::dropIfExists('matriculas');
    }
};
