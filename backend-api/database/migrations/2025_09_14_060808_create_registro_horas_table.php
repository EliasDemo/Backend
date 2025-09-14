<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('registro_horas', function (Blueprint $table) {
            $table->id();

            // Vínculos
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('ep_sede_id')->constrained('ep_sede')->restrictOnDelete();
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->restrictOnDelete(); // se puede derivar de ep_sede
            $table->foreignId('periodo_id')->constrained('periodos_academicos')->restrictOnDelete();

            // Datos del registro
            $table->date('fecha');                                        // día del servicio
            $table->unsignedSmallInteger('minutos');                      // 90, 120, etc. (0–1440)
            $table->string('actividad', 200);                             // título breve
            $table->text('detalle')->nullable();                          // descripción opcional
            $table->string('evidencia_url', 255)->nullable();             // enlace/archivo

            // Flujo de estado
            $table->enum('estado', ['BORRADOR','ENVIADO','OBSERVADO','APROBADO','RECHAZADO'])
                  ->default('BORRADOR');

            $table->timestamps();

            // Índices
            $table->index(['estudiante_id','estado'], 'idx_rh_est_estado');
            $table->index(['ep_sede_id','periodo_id','estado'], 'idx_rh_epsede_periodo_estado');
            $table->index(['sede_id','periodo_id'], 'idx_rh_sede_periodo');
            $table->index(['fecha'], 'idx_rh_fecha');
        });

        // (Opcional, MySQL 8+)
        DB::statement("
            ALTER TABLE registro_horas
            ADD CONSTRAINT chk_registro_horas_minutos
            CHECK (minutos BETWEEN 1 AND 1440)
        ");
    }

    public function down(): void {
        Schema::dropIfExists('registro_horas');
    }
};
