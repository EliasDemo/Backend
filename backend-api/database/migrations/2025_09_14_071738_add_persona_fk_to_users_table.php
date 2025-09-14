<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
        {
            Schema::table('users', function (Blueprint $table) {
                // Si aún no existe la columna, primero créala:
                if (!Schema::hasColumn('users','persona_id')) {
                    $table->foreignId('persona_id')->nullable()->after('id');
                }

                // Agrega la FK
                $table->foreign('persona_id')
                    ->references('id')
                    ->on('personas')
                    ->nullOnDelete();
            });
        }

    public function down(): void
        {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['persona_id']);   // o: $table->dropForeign('users_persona_id_foreign');
                // Si quieres también quitar la columna:
                // $table->dropColumn('persona_id');
            });
        }

};
