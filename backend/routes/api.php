<?php

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rotas públicas (login, etc.)
// ...

// Rotas protegidas pelo Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    
    Route::post('/products', [ProductController::class, 'store']);
    // ...outras rotas
});
