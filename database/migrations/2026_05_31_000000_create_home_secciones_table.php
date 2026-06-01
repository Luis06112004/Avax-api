<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('home_secciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo'); // hero, beneficios, destacados, promo_banner, categorias, nuevos, marcas, exploracion
            $table->string('titulo')->nullable();
            $table->string('subtitulo', 500)->nullable();
            $table->json('configuracion');
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_secciones');
    }
};
