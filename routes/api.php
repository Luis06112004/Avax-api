<?php

use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\SyncController;
use App\Http\Controllers\Api\Admin\StatsController;
use App\Http\Controllers\Api\Admin\BannerController;
use App\Http\Controllers\Api\Admin\ClienteController;
use App\Http\Controllers\Api\Admin\CuponController;
use App\Http\Controllers\Api\Admin\ConfiguracionController;
use App\Http\Controllers\Api\Admin\AdminHomeController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\Shop\CatalogController;
use App\Http\Controllers\Api\Shop\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('admin/register', 'adminRegister');
    Route::post('admin/login', 'adminLogin');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', 'logout');
        Route::get('me', 'me');
    });
});

Route::prefix('shop')->controller(CatalogController::class)->group(function () {
    Route::get('productos', 'index');
    Route::get('productos/destacados', 'destacados');
    Route::get('productos/populares', 'populares');
    Route::get('productos/ofertas', 'ofertas');
    Route::get('productos/{slug}', 'show');
    Route::get('marcas', 'marcas');
    Route::get('categorias', 'categorias');
});

// Crear pedido: PÚBLICO (permite compra como invitado). Si viene token,
// el pedido se asocia al usuario; si no, queda como invitado (user_id null).
Route::prefix('shop')->controller(OrderController::class)->group(function () {
    Route::post('pedidos', 'store');
});

// Ver pedidos propios: requiere sesión.
Route::prefix('shop')->middleware('auth:sanctum')->controller(OrderController::class)->group(function () {
    Route::get('pedidos', 'index');
    Route::get('pedidos/{numero}', 'show');
});

// Homepage pública (secciones activas resueltas con datos reales)
Route::get('home/secciones', [HomeController::class, 'index']);

Route::prefix('admin')->middleware('auth:sanctum')->group(function () {

    Route::apiResource('productos', ProductController::class)
        ->parameters(['productos' => 'product']);

    Route::prefix('sync')->controller(SyncController::class)->group(function () {
        Route::post('run', 'run');
        Route::post('start', 'start');
        Route::post('run-job/{id}', 'runJob');
        Route::get('status/{id}', 'status');
        Route::get('{id}/cambios', 'cambios');
        Route::get('last', 'last');
    });

    Route::get('stats', [StatsController::class, 'index']);

    Route::get('banners', [BannerController::class, 'index']);
    Route::post('banners', [BannerController::class, 'store']);
    Route::post('banners/{id}', [BannerController::class, 'update']);
    Route::delete('banners/{id}', [BannerController::class, 'destroy']);

    Route::get('clientes', [ClienteController::class, 'index']);
    Route::get('clientes/{id}/pedidos', [ClienteController::class, 'pedidos']);
    Route::patch('clientes/{id}/estado', [ClienteController::class, 'toggleEstado']);

    Route::get('cupones', [CuponController::class, 'index']);
    Route::post('cupones', [CuponController::class, 'store']);
    Route::put('cupones/{id}', [CuponController::class, 'update']);
    Route::patch('cupones/{id}/estado', [CuponController::class, 'toggleEstado']);
    Route::delete('cupones/{id}', [CuponController::class, 'destroy']);

    Route::get('configuracion', [ConfiguracionController::class, 'index']);
    Route::put('configuracion', [ConfiguracionController::class, 'update']);
    Route::post('configuracion/logo', [ConfiguracionController::class, 'uploadLogo']);

    // Homepage CMS — secciones (reordenar ANTES de {id} para evitar colisión)
    Route::get('home/secciones', [AdminHomeController::class, 'index']);
    Route::post('home/secciones', [AdminHomeController::class, 'store']);
    Route::put('home/secciones/reordenar', [AdminHomeController::class, 'reorder']);
    Route::get('home/secciones/{id}', [AdminHomeController::class, 'show']);
    Route::put('home/secciones/{id}', [AdminHomeController::class, 'update']);
    Route::delete('home/secciones/{id}', [AdminHomeController::class, 'destroy']);
});