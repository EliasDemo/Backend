<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universidad_id')->constrained('universidades')->cascadeOnDelete();
            $table->string('nombre');                    // p.ej., Lima, Juliaca, Tarapoto
            $table->boolean('es_principal')->default(false);

            // NUEVO: soporte para suspensión (borrado lógico)
            $table->boolean('esta_suspendida')->default(false);
            $table->timestamp('suspendida_desde')->nullable();

            $table->timestamps();

            $table->unique(['universidad_id','nombre']); // una vez por universidad
            $table->index(['universidad_id','esta_suspendida'], 'sedes_uni_suspendida_idx');
        });
    }

    public function down(): void {
        Schema::dropIfExists('sedes');
    }
};
