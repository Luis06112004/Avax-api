<?php

use App\Http\Controllers\Api\Admin\ProductController;
use Illuminate\Support\Facades\Route;

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
});
