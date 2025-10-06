<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // login con username
            $table->string('username', 50)->unique();

            // datos personales
            $table->string('first_name', 100); // nombre
            $table->string('last_name', 150);  // apellidos

            // email solo para recuperación
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();

            // autenticación
            $table->string('password');

            // recuperación por código (OTP, SMS, app, etc.)
            $table->string('recovery_code')->nullable();
            $table->timestamp('recovery_expires_at')->nullable();

            // PERFIL
            $table->string('profile_photo', 2048)->nullable(); // URL o path largo

            // estado de la cuenta
            $table->enum('status', ['active', 'view_only', 'suspended'])->default('active');

            // otros campos estándar
            $table->rememberToken();
            $table->timestamps();

            // índices útiles para búsquedas por nombre
            $table->index(['last_name', 'first_name']);
        });

        // tokens de reseteo por email (estándar de Laravel)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // sesiones (estándar de Laravel)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()
                  ->constrained('users')->nullOnDelete(); // enlaza y limpia si se borra el user
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        // IMPORTANTE: borrar en orden por la FK
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
