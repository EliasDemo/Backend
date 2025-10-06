<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();

            $table->morphs('certificable');
            $table->foreignId('persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->foreignId('estudiante_id')->nullable()->constrained('estudiantes')->nullOnDelete();

            $table->enum('rol', ['PARTICIPANTE','PONENTE','ORGANIZADOR','RESPONSABLE','DOCENTE','INVITADO'])
                  ->default('PARTICIPANTE');
            $table->unsignedSmallInteger('minutos')->default(0);

            $table->string('codigo_unico', 40)->unique();
            $table->enum('estado', ['BORRADOR','EMITIDO','REVOCADO'])->default('BORRADOR');
            $table->timestamp('emitido_at')->nullable();

            $table->string('archivo_disk', 40)->nullable();
            $table->string('archivo_path', 255)->nullable();

            $table->json('extra')->nullable();

            $table->timestamps();

            $table->index(['certificable_type','certificable_id','estado'], 'idx_cert_scope');
        });
    }

    public function down(): void {
        Schema::dropIfExists('certificados');
    }
};
