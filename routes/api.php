<?php

use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\SyncController;
use App\Http\Controllers\Api\Shop\CatalogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API — Admin
|--------------------------------------------------------------------------
| Rutas abiertas sin auth por ahora. Cuando armemos login con Sanctum,
| se envolverán en middleware('auth:sanctum').
*/

// Catalogo publico (lectura) para la tienda
Route::prefix('shop')->controller(CatalogController::class)->group(function () {
    Route::get('productos', 'index');
    Route::get('productos/destacados', 'destacados');
    Route::get('productos/populares', 'populares');
    Route::get('productos/ofertas', 'ofertas');
    Route::get('productos/{slug}', 'show');
    Route::get('marcas', 'marcas');
    Route::get('categorias', 'categorias');
});

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
