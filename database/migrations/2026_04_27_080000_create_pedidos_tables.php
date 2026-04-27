<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 30)->unique();
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->enum('estado', [
                'pendiente', 'pagado', 'enviado', 'entregado', 'cancelado',
            ])->default('pendiente')->index();

            // Datos de envío (snapshot)
            $table->string('contacto_nombres', 120);
            $table->string('contacto_apellidos', 120);
            $table->string('contacto_email', 191);
            $table->string('contacto_telefono', 30);
            $table->string('envio_departamento', 100);
            $table->string('envio_provincia', 100);
            $table->string('envio_distrito', 100);
            $table->string('envio_direccion', 255);
            $table->string('envio_referencia', 255)->nullable();
            $table->text('envio_notas')->nullable();

            // Método de envío
            $table->string('envio_metodo_id', 30);
            $table->string('envio_metodo_nombre', 120);
            $table->decimal('envio_costo', 12, 2)->default(0);

            // Pago (snapshot, NUNCA datos sensibles completos)
            $table->enum('pago_metodo', ['tarjeta', 'yape', 'transferencia']);
            $table->string('pago_referencia', 60)->nullable();

            // Totales
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);

            $table->timestamp('confirmado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()
                ->constrained('productos')->nullOnDelete();
            $table->string('producto_slug', 220);
            $table->string('producto_nombre', 200);
            $table->string('marca', 150)->nullable();
            $table->string('imagen', 500)->nullable();
            $table->string('talla', 50)->nullable();
            $table->string('color', 80)->nullable();
            $table->decimal('precio_unitario', 12, 2);
            $table->integer('cantidad');
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index(['pedido_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
        Schema::dropIfExists('pedidos');
    }
};
