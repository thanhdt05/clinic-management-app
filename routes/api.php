<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\SpecialtyController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::middleware('permission')->group(function () {
        Route::apiResource('users', UserController::class);

        Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])
            ->name('users.updateStatus');

        Route::apiResource('specialties', SpecialtyController::class);
    });
});
