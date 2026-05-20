<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_tienda')->nullable();
            $table->text('descripcion_tienda')->nullable();
            $table->string('email_contacto')->nullable();
            $table->string('telefono_contacto')->nullable();
            $table->string('direccion')->nullable();
            $table->string('moneda', 10)->default('PEN');
            $table->string('simbolo_moneda', 5)->default('S/');
            $table->string('logo_url')->nullable();
            $table->string('color_primario', 20)->default('#7c3aed');
            $table->json('redes_sociales')->nullable();
            $table->decimal('envio_gratis_desde', 10, 2)->nullable();
            $table->decimal('costo_envio_base', 10, 2)->default(0);
            $table->decimal('igv_porcentaje', 5, 2)->default(18);
            $table->string('meta_titulo', 60)->nullable();
            $table->string('meta_descripcion', 160)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};