<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('encargados_sede', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sede_id')
                  ->constrained('sedes')
                  ->cascadeOnDelete();

            $table->foreignId('periodo_id')
                  ->constrained('periodos_academicos')
                  ->cascadeOnDelete();

            $table->foreignId('persona_id')
                  ->constrained('personas')
                  ->restrictOnDelete();

            $table->string('cargo', 80)->default('ENCARGADO DE SEDE');
            $table->boolean('activo')->default(true);

            $table->timestamps();

            // Uno por sede y periodo
            $table->unique(['sede_id','periodo_id'], 'ux_encargado_sede_periodo');

            // Índices útiles
            $table->index(['persona_id','activo'], 'idx_encargado_persona_activo');
            $table->index(['sede_id','periodo_id','activo'], 'idx_encargado_sede_periodo_activo');
        });
    }

    public function down(): void {
        Schema::dropIfExists('encargados_sede');
    }
};
