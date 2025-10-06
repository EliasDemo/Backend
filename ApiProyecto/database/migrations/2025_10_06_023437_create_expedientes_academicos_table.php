<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes_academicos', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FKs
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unsignedBigInteger('ep_sede_id');
            $table->foreign('ep_sede_id')
                ->references('id')->on('ep_sede')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Datos del expediente
            $table->string('codigo_estudiante');             // suele ser único
            $table->string('grupo')->nullable();
            $table->string('correo_institucional')->nullable();

            // Estado (según ERD original)
            $table->enum('estado', ['ACTIVO', 'SUSPENDIDO', 'EGRESADO', 'CESADO'])
                  ->default('ACTIVO');

            // Reglas / índices
            $table->unique('codigo_estudiante');                 // evita duplicados globales
            $table->unique(['user_id', 'ep_sede_id']);           // 1 expediente por EP_SEDE por usuario
            $table->index('estado');
            $table->unique('correo_institucional');              // acepta múltiples NULL en MySQL

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes_academicos');
    }
};
