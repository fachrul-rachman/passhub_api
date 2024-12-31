<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PasswordController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::apiResource('categories', CategoryController::class);
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::apiResource('passwords', PasswordController::class);
    Route::post('passwords/generate', [PasswordController::class, 'generatePassword']);
    Route::get('passwords/category/{category_id}', [PasswordController::class, 'passwordsByCategory']);
});