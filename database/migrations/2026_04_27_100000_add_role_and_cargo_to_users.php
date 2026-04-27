<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distinción entre usuarios cliente (tienda) y usuarios admin (CMS).
 * Solo los users con role='admin' pueden ingresar al panel.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // SQLite no soporta enum, usamos string
            $table->string('role', 20)->default('cliente')->index()->after('password');
            $table->string('cargo', 120)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'cargo']);
        });
    }
};
