<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('escuelas_profesionales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('facultad_id')
                  ->constrained('facultades')
                  ->cascadeOnDelete();

            $table->string('codigo', 32);     // corto, estable (normalizaremos a UPPER en el modelo)
            $table->string('nombre', 150);
            $table->string('slug', 160)->nullable(); // para URLs/filtros

            // Suspensión (no borramos registros)
            $table->boolean('esta_suspendida')->default(false);
            $table->timestamp('suspendida_desde')->nullable();

            $table->timestamps();

            // Únicos por facultad
            $table->unique(['facultad_id','codigo']);
            $table->unique(['facultad_id','nombre']);
            $table->unique(['facultad_id','slug']);

            // Índices para filtros
            $table->index(['facultad_id','esta_suspendida'], 'ep_fac_suspendida_idx');
        });
    }

    public function down(): void {
        Schema::dropIfExists('escuelas_profesionales');
    }
};
