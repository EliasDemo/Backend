<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vm_asistencias', function (Blueprint $table) {
            $table->bigIncrements('id');

            // ===== FKs obligatorias/opcionales =====
            $table->unsignedBigInteger('sesion_id');
            $table->foreign('sesion_id')
                ->references('id')->on('vm_sesiones')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unsignedBigInteger('expediente_id');
            $table->foreign('expediente_id')
                ->references('id')->on('expedientes_academicos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unsignedBigInteger('participacion_id')->nullable();
            $table->foreign('participacion_id')
                ->references('id')->on('vm_participaciones')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->unsignedBigInteger('qr_token_id')->nullable();
            $table->foreign('qr_token_id')
                ->references('id')->on('vm_qr_tokens')
                ->onUpdate('cascade')
                ->onDelete('set null');

            // ===== Datos =====
            $table->enum('metodo', ['QR', 'MANUAL', 'IMPORTADO', 'AJUSTE'])->default('MANUAL');

            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();

            $table->enum('estado', ['PENDIENTE', 'VALIDADO', 'ANULADO'])->default('PENDIENTE');

            $table->unsignedSmallInteger('minutos_validados')->default(0);

            // ===== Índices / reglas =====
            // Un registro de asistencia por sesión y expediente
            $table->unique(['sesion_id', 'expediente_id']);
            // Si usas participaciones, evita duplicar dentro de la sesión
            $table->unique(['sesion_id', 'participacion_id']);

            $table->index('metodo');
            $table->index('estado');
            $table->index('qr_token_id');
            $table->index('check_in_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vm_asistencias');
    }
};
