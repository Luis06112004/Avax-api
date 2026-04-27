<?php

use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\SyncController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Shop\CatalogController;
use App\Http\Controllers\Api\Shop\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API — Auth (clientes de la tienda)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');

    // Endpoints específicos del CMS (requieren código de invitación / role admin)
    Route::post('admin/register', 'adminRegister');
    Route::post('admin/login', 'adminLogin');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', 'logout');
        Route::get('me', 'me');
    });
});

/*
|--------------------------------------------------------------------------
| API — Shop (catalogo + pedidos)
|--------------------------------------------------------------------------
*/

// Catalogo publico (lectura)
Route::prefix('shop')->controller(CatalogController::class)->group(function () {
    Route::get('productos', 'index');
    Route::get('productos/destacados', 'destacados');
    Route::get('productos/populares', 'populares');
    Route::get('productos/ofertas', 'ofertas');
    Route::get('productos/{slug}', 'show');
    Route::get('marcas', 'marcas');
    Route::get('categorias', 'categorias');
});

// Pedidos del cliente (autenticados)
Route::prefix('shop')->middleware('auth:sanctum')->controller(OrderController::class)->group(function () {
    Route::get('pedidos', 'index');
    Route::post('pedidos', 'store');
    Route::get('pedidos/{numero}', 'show');
});

/*
|--------------------------------------------------------------------------
| API — Admin
|--------------------------------------------------------------------------
| Rutas abiertas sin auth por ahora. Cuando armemos login con Sanctum,
| se envolverán en middleware('auth:sanctum').
*/
Route::prefix('admin')->group(function () {
    Route::apiResource('productos', ProductController::class)
        ->parameters(['productos' => 'product']);

    // Sync con e-commerce eless-style (catalogo de moda)
    Route::prefix('sync')->controller(SyncController::class)->group(function () {
        Route::post('run', 'run');
        Route::post('start', 'start');
        Route::post('run-job/{id}', 'runJob');
        Route::get('status/{id}', 'status');
        Route::get('{id}/cambios', 'cambios');
        Route::get('last', 'last');
    });
});
