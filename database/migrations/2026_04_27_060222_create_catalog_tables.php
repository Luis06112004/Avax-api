<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas del catalogo sincronizado desde el e-commerce externo
 * (espejo de https://api1.eless.com.pe/api/v1/...).
 *
 * Conviven con la tabla `products` original del CRUD manual sin chocar.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ecommerce_id')->unique();
            $table->string('nombre', 150);
            $table->string('slug', 191)->index();
            $table->string('logo', 500)->nullable();
            $table->integer('productos_count')->default(0);
            $table->timestamps();
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ecommerce_id')->unique();
            $table->string('nombre', 150);
            $table->string('slug', 191)->index();
            $table->text('descripcion')->nullable();
            $table->string('imagen', 500)->nullable();
            $table->unsignedBigInteger('padre_ecommerce_id')->nullable()->index();
            $table->integer('productos_count')->default(0);
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ecommerce_id')->unique();
            $table->string('nombre', 200);
            $table->string('slug', 220)->index();
            $table->string('sku', 100)->index();
            $table->text('descripcion_corta')->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 12, 2)->default(0);
            $table->decimal('precio_comparacion', 12, 2)->nullable();
            $table->unsignedBigInteger('marca_id')->nullable()->index();
            $table->string('marca_nombre', 150)->nullable();
            $table->string('categoria_principal', 150)->nullable();
            $table->string('categoria_slug', 191)->nullable()->index();
            $table->string('color', 80)->nullable();
            $table->string('color_hex', 10)->nullable();
            $table->string('genero', 30)->nullable()->index();
            $table->string('video_url', 500)->nullable();
            $table->string('imagen_principal', 500)->nullable();
            $table->boolean('en_stock')->default(false);
            $table->integer('stock_total')->default(0);
            $table->boolean('activo')->default(true)->index();
            $table->boolean('nuevo')->default(false);
            $table->boolean('destacado')->default(false);
            $table->timestamp('ultima_sync_at')->nullable();
            $table->json('ecommerce_raw')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('marca_id')->references('id')->on('marcas')->nullOnDelete();
        });

        Schema::create('producto_tallas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('talla', 50);
            $table->decimal('ajuste_precio', 10, 2)->default(0);
            $table->decimal('precio_final', 12, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->boolean('es_predeterminada')->default(false);
            $table->timestamps();

            $table->index(['producto_id', 'talla']);
        });

        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('url', 500);
            $table->string('alt', 200)->nullable();
            $table->boolean('es_principal')->default(false);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('categoria_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();

            $table->unique(['producto_id', 'categoria_id']);
        });

        Schema::create('ecommerce_sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->enum('estado', ['en_progreso', 'completado', 'error', 'cancelado'])
                ->default('en_progreso')->index();
            $table->timestamp('iniciado_at');
            $table->timestamp('terminado_at')->nullable();
            $table->integer('duracion_segundos')->nullable();
            $table->integer('progreso_pct')->default(0);
            $table->string('fase_actual', 100)->nullable();
            $table->integer('total_productos')->default(0);
            $table->integer('procesados')->default(0);
            $table->integer('nuevos')->default(0);
            $table->integer('actualizados')->default(0);
            $table->integer('removidos')->default(0);
            $table->integer('sin_cambios')->default(0);
            $table->integer('total_requests_http')->default(0);
            $table->text('error_mensaje')->nullable();
            $table->unsignedBigInteger('iniciado_por')->nullable();
            $table->timestamps();
        });

        Schema::create('ecommerce_sync_cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_id')->constrained('ecommerce_sync_jobs')->cascadeOnDelete();
            $table->enum('tipo', ['nuevo', 'actualizado', 'removido'])->index();
            $table->string('subtipo', 30)->nullable();
            $table->unsignedBigInteger('producto_id')->nullable()->index();
            $table->unsignedBigInteger('ecommerce_id')->index();
            $table->string('sku', 100)->nullable();
            $table->string('nombre', 200);
            $table->json('antes')->nullable();
            $table->json('despues')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_sync_cambios');
        Schema::dropIfExists('ecommerce_sync_jobs');
        Schema::dropIfExists('categoria_producto');
        Schema::dropIfExists('producto_imagenes');
        Schema::dropIfExists('producto_tallas');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('marcas');
    }
};
