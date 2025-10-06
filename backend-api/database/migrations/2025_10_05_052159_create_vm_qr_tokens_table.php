<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vm_qr_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sesion_id')->constrained('vm_sesiones')->cascadeOnDelete();

            $table->string('token', 24)->unique();
            $table->timestamp('usable_from')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->unsignedInteger('max_usos')->nullable();
            $table->unsignedInteger('usos')->default(0);
            $table->boolean('activo')->default(true);

            $table->decimal('latitud', 10,7)->nullable();
            $table->decimal('longitud', 10,7)->nullable();
            $table->unsignedInteger('geofence_radio_m')->nullable();
            $table->boolean('requiere_gps')->default(false);

            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['sesion_id','activo'], 'idx_vm_qr_sesion_activo');
        });
    }

    public function down(): void {
        Schema::dropIfExists('vm_qr_tokens');
    }
};
