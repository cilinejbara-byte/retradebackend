<?php

use App\Http\Controllers\API\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\ChatBotController;

Route::post('/chat', [ChatBotController::class, 'ask']);
Route::controller(RegisterController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::middleware('auth:sanctum')->post('logout', 'logout');
});

Route::middleware(['optional.auth'])->group(function () {
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
});

Route::middleware('auth.api:sanctum')->group( function () {
    Route::resource('products', ProductController::class)->except(['index', 'show']);
    Route::resource('categories', CategoryController::class)->except(['index', 'show']);
    Route::post('products/{product}/rate', [ProductController::class, 'rate']);

});

Route::get('/user', function (Request $request) {
    return $request->user()->load('products');
})->middleware('auth.api:sanctum');

Route::middleware('auth.api:sanctum')->group(function () {
    Route::post('/products/{id}/like', [ProductController::class, 'like']);
    Route::delete('/products/{id}/like', [ProductController::class, 'unlike']);
});

Route::middleware('auth.api:sanctum')->group(function () {
    Route::get('/cart', [CartController::class, 'viewCart']);
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::delete('/cart/remove/{productId}', [CartController::class, 'removeFromCart']);
});

Route::middleware('auth.api:sanctum')->get('/user/liked-products', [ProductController::class, 'likedProducts']);


Route::get('/clear-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    return response()->json(['message' => 'تم تنظيف الكاش بنجاح على السيرفر!']);
});