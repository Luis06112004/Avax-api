<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('brand');
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('old_price', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->json('sizes')->nullable();
            $table->json('colors')->nullable();
            $table->enum('badge', ['HOT', 'NEW', 'SALE'])->nullable();
            $table->enum('status', ['active', 'draft', 'out_of_stock'])->default('draft');
            $table->json('images')->nullable();
            $table->timestamps();

            $table->index('brand');
            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
