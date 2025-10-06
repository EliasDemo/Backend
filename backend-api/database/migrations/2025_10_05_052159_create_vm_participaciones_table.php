<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vm_participaciones', function (Blueprint $table) {
            $table->id();

            $table->morphs('participable'); // vm_proyectos o vm_eventos
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiantes')->nullOnDelete();
            $table->foreignId('persona_id')->nullable()->constrained('personas')->nullOnDelete();

            $table->string('externo_nombre', 180)->nullable();
            $table->string('externo_documento', 30)->nullable();

            $table->enum('rol', ['PARTICIPANTE','PONENTE','ORGANIZADOR','RESPONSABLE','DOCENTE','INVITADO'])
                  ->default('PARTICIPANTE');
            $table->enum('estado', ['INSCRITO','CONFIRMADO','RETIRADO','RECHAZADO'])->default('INSCRITO');

            $table->timestamps();

            $table->unique(['participable_type','participable_id','estudiante_id','rol'], 'ux_vm_part_est_rol');
            $table->index(['participable_type','participable_id','rol','estado'], 'idx_vm_part_scope');
        });
    }

    public function down(): void {
        Schema::dropIfExists('vm_participaciones');
    }
};
