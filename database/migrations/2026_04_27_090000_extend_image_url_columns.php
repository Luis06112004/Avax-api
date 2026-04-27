<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite almacenar imágenes como data URLs base64 inline (subidas desde el
 * CMS sin endpoint de upload dedicado). Las URLs http(s) normales seguirán
 * funcionando exactamente igual.
 */
return new class extends Migration {
    public function up(): void
    {
        // SQLite (driver por defecto en dev) ignora el cambio de tipo en
        // columnas existentes salvo con doctrine/dbal. Para mantenerlo simple
        // recreamos la columna a través de Schema::table en MySQL/Postgres,
        // y dejamos pasar para SQLite (la columna allí es TEXT virtualmente
        // sin límite real de longitud).
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') return;

        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->longText('url')->change();
        });
        Schema::table('productos', function (Blueprint $table) {
            $table->longText('imagen_principal')->nullable()->change();
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') return;

        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->string('url', 500)->change();
        });
        Schema::table('productos', function (Blueprint $table) {
            $table->string('imagen_principal', 500)->nullable()->change();
        });
    }
};
