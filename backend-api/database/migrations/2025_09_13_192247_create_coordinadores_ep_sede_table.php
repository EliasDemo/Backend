<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('coordinadores_ep_sede', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ep_sede_id')
                  ->constrained('ep_sede')
                  ->cascadeOnDelete();

            $table->foreignId('periodo_id')
                  ->constrained('periodos_academicos')
                  ->cascadeOnDelete();

            $table->foreignId('persona_id')
                  ->constrained('personas')
                  ->restrictOnDelete();

            // Datos mínimos del cargo
            $table->string('cargo', 80)->default('COORDINADOR EP');
            $table->boolean('activo')->default(true);

            $table->timestamps();

            // Uno por EP–Sede y periodo
            $table->unique(['ep_sede_id','periodo_id'], 'ux_coord_epsede_periodo');

            // Índices útiles
            $table->index(['persona_id','activo'], 'idx_coord_persona_activo');
            $table->index(['ep_sede_id','periodo_id','activo'], 'idx_coord_epsede_periodo_activo');
        });
    }

    public function down(): void {
        Schema::dropIfExists('coordinadores_ep_sede');
    }
};
