<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('imagenes', function (Blueprint $table) {
            $table->id();

            $table->morphs('imageable');

            $table->string('disk', 40)->default('public');
            $table->string('path', 255);
            $table->string('url', 255)->nullable();

            $table->string('titulo', 160)->nullable();
            $table->string('caption', 255)->nullable();
            $table->unsignedInteger('orden')->default(0);

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->json('metadata')->nullable();
            $table->enum('visibilidad', ['PUBLICA','PRIVADA'])->default('PUBLICA');

            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['imageable_type','imageable_id','orden'], 'idx_img_order');
        });
    }

    public function down(): void {
        Schema::dropIfExists('imagenes');
    }
};
