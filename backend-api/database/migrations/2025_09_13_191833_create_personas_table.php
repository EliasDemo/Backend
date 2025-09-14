<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();

            // Identificación
            $table->string('dni', 12)->nullable()->unique();     // DNI o CE
            $table->string('apellidos');                         // UPPER en modelo
            $table->string('nombres');                           // Title Case en modelo

            // Contacto
            $table->string('email_institucional')->nullable()->unique(); // normalizado lower
            $table->string('email_personal')->nullable();                // normalizado lower
            $table->string('celular', 20)->nullable();                   // sólo dígitos en modelo

            // Datos opcionales
            $table->enum('sexo', ['M','F','X'])->nullable();
            $table->date('fecha_nacimiento')->nullable();

            // Columna generada para búsquedas: "APELLIDOS NOMBRES"
            $table->string('nombre_busqueda')
                  ->storedAs("CONCAT(UPPER(`apellidos`),' ',UPPER(`nombres`))");

            $table->timestamps();

            // Índices
            $table->index(['apellidos','nombres']);
            $table->index('nombre_busqueda', 'personas_nombre_busqueda_idx');
        });

        // (Opcional) Restringir dominio institucional (MySQL 8+; si no, comentar)
        DB::statement("
            ALTER TABLE personas
            ADD CONSTRAINT chk_email_upeu
            CHECK (email_institucional IS NULL OR email_institucional LIKE '%@upeu.edu.pe')
        ");
    }

    public function down(): void {
        Schema::dropIfExists('personas');
    }
};
