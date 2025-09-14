<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universidades', function (Blueprint $table) {
            $table->id();

            // === Campos oficiales SUNEDU ===
            $table->string('codigo_entidad', 20)->unique();        // CODIGO_ENTIDAD (SUNEDU)
            $table->string('nombre');                               // NOMBRE (SUNEDU)
            $table->enum('tipo_gestion', ['PUBLICO','PRIVADO']);    // TIPO_GESTION (SUNEDU)
            $table->enum('estado_licenciamiento', [                 // ESTADO_LICENCIAMIENTO (SUNEDU)
                'CON_IO_NOTIFICADO',
                'CON_IRD',
                'CON_IRD_DESFAVORABLE',
                'EN_VERIFICACION_PRESENCIAL',
                'CON_IVP',
                'LICENCIA_DENEGADA',
                'LICENCIA_OTORGADA',
                'NINGUNO',
            ])->default('NINGUNO');
            $table->unsignedSmallInteger('periodo_licenciamiento')->nullable(); // PERIODO_LICENCIAMIENTO (SUNEDU)

            // Ubicación del local principal (SUNEDU)
            $table->string('departamento_local')->nullable();
            $table->string('provincia_local')->nullable();
            $table->string('distrito_local')->nullable();
            $table->decimal('latitud_ubicacion', 10, 7)->nullable();   // LATITUD_UBICACION
            $table->decimal('longitud_ubicacion', 10, 7)->nullable();  // LONGITUD_UBICACION

            // === Campos que SUNEDU muestra en su lista y útiles de gestión ===
            $table->date('fecha_licenciamiento')->nullable();          // FECHA DE LICENCIAMIENTO (SUNEDU)
            $table->string('resolucion_licenciamiento')->nullable();   // Nº/identificador de resolución (SUNEDU)

            // === Complementarios (opcionales) ===
            $table->string('sigla', 30)->nullable();
            $table->string('ruc', 15)->nullable()->unique();
            $table->string('domicilio_legal')->nullable();
            $table->string('web_url')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email_contacto')->nullable();
            $table->date('licencia_vigencia_desde')->nullable();
            $table->date('licencia_vigencia_hasta')->nullable();

            // Auditoría
            $table->timestamps();

            // Índices útiles para filtros/reportes
            $table->index(['tipo_gestion', 'estado_licenciamiento']);
            $table->index(['departamento_local', 'provincia_local']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universidades');
    }
};
