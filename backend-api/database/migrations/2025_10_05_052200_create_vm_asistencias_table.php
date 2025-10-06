<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vm_asistencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sesion_id')->constrained('vm_sesiones')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('participacion_id')->nullable()->constrained('vm_participaciones')->nullOnDelete();
            $table->foreignId('qr_token_id')->nullable()->constrained('vm_qr_tokens')->nullOnDelete();

            $table->enum('metodo', ['QR','CODIGO','MANUAL'])->default('QR');

            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();

            $table->decimal('latitud', 10,7)->nullable();
            $table->decimal('longitud', 10,7)->nullable();
            $table->unsignedInteger('distancia_m')->nullable();
            $table->boolean('gps_valido')->default(false);
            $table->string('device_info', 120)->nullable();
            $table->string('ip', 45)->nullable();

            $table->enum('estado', ['PRESENTE','TARDANZA','AUSENTE','JUSTIFICADO'])->default('PRESENTE');
            $table->unsignedSmallInteger('minutos_validados')->default(0);

            $table->timestamps();

            $table->unique(['sesion_id','estudiante_id'], 'ux_vm_asistencia_unica');
            $table->index(['sesion_id','estado'], 'idx_vm_asist_sesion_estado');
            $table->index(['estudiante_id','estado'], 'idx_vm_asist_est_estado');
        });
    }

    public function down(): void {
        Schema::dropIfExists('vm_asistencias');
    }
};
