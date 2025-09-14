<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('facultades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('universidad_id')
                  ->constrained('universidades')
                  ->cascadeOnDelete();

            $table->string('codigo', 32);          // corto, estable, normalizado en el modelo (UPPER + trim)
            $table->string('nombre', 150);
            $table->string('slug', 160)->nullable(); // opcional, para URLs/filters

            // Soporte de suspensión (no borramos información)
            $table->boolean('esta_suspendida')->default(false);
            $table->timestamp('suspendida_desde')->nullable();

            $table->timestamps();

            // Únicos por universidad
            $table->unique(['universidad_id','codigo']);
            $table->unique(['universidad_id','nombre']);
            $table->unique(['universidad_id','slug']);

            // Índices útiles de filtrado
            $table->index(['universidad_id','esta_suspendida'], 'facultades_uni_suspendida_idx');
        });

        /**
         * OPCIONAL (si usas MySQL 8+ y quieres reforzar unicidad por código ya normalizado):
         * Puedes agregar una columna generada con UPPER(TRIM(codigo)) y usarla en el unique.
         * Laravel soporta ->storedAs() en MySQL 8+. Si lo deseas, crea una migración aparte:
         *
         * Schema::table('facultades', function (Blueprint $table) {
         *     $table->string('codigo_norm', 32)->storedAs('UPPER(TRIM(`codigo`))');
         *     $table->unique(['universidad_id','codigo_norm'], 'facultades_uni_codigo_norm_unique');
         * });
         *
         * Y puedes eliminar el unique original de (universidad_id, codigo) si ya usas codigo_norm.
         */
    }

    public function down(): void {
        Schema::dropIfExists('facultades');
    }
};
