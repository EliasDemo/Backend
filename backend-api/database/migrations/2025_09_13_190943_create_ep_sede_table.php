<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('ep_sede', function (Blueprint $table) {
            $table->id();

            $table->foreignId('escuela_profesional_id')
                  ->constrained('escuelas_profesionales')
                  ->cascadeOnDelete();

            $table->foreignId('sede_id')
                  ->constrained('sedes')
                  ->cascadeOnDelete();

            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();

            $table->timestamps();

            // Evita duplicados para un mismo intervalo de inicio
            $table->unique(['escuela_profesional_id','sede_id','vigente_desde'], 'ux_ep_sede_vig');

            // Índices para búsquedas comunes
            $table->index(['escuela_profesional_id','sede_id'], 'idx_ep_sede');
            $table->index(['sede_id','vigente_hasta'], 'idx_sede_hasta');
            $table->index(['escuela_profesional_id','vigente_hasta'], 'idx_ep_hasta');
        });

        // (Opcional) chequeo de coherencia de fechas en MySQL 8+
        // En versiones antiguas de MySQL los CHECK se ignoran.
        DB::statement("
            ALTER TABLE ep_sede
            ADD CONSTRAINT chk_ep_sede_fechas
            CHECK (vigente_hasta IS NULL OR vigente_desde IS NULL OR vigente_hasta >= vigente_desde)
        ");
    }

    public function down(): void {
        Schema::dropIfExists('ep_sede');
    }
};
