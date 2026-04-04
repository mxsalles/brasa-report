<?php

use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::post('/auth/senha/esqueci', [PasswordResetController::class, 'enviarToken']);
Route::post('/auth/senha/redefinir', [PasswordResetController::class, 'redefinir']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});
