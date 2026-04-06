<?php

use App\Http\Controllers\AreaMonitoradaController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrigadaController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::post('/auth/senha/esqueci', [PasswordResetController::class, 'enviarToken']);
Route::post('/auth/senha/redefinir', [PasswordResetController::class, 'redefinir']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Papéis futuros: gestor, admin
    Route::get('/brigadas', [BrigadaController::class, 'index']);
    // Papéis futuros: admin
    Route::post('/brigadas', [BrigadaController::class, 'store']);
    // Papéis futuros: gestor, admin
    Route::get('/brigadas/{brigada}', [BrigadaController::class, 'show']);
    // Papéis futuros: admin
    Route::put('/brigadas/{brigada}', [BrigadaController::class, 'update']);
    // Papéis futuros: admin
    Route::delete('/brigadas/{brigada}', [BrigadaController::class, 'destroy']);
    // Papéis futuros: brigadista, gestor, admin
    Route::patch('/brigadas/{brigada}/localizacao', [BrigadaController::class, 'atualizarLocalizacao']);

    // Papéis futuros: gestor, admin
    Route::get('/areas-monitoradas', [AreaMonitoradaController::class, 'index']);
    // Papéis futuros: admin
    Route::post('/areas-monitoradas', [AreaMonitoradaController::class, 'store']);
    // Papéis futuros: gestor, admin
    Route::get('/areas-monitoradas/{area}', [AreaMonitoradaController::class, 'show']);
    // Papéis futuros: admin
    Route::put('/areas-monitoradas/{area}', [AreaMonitoradaController::class, 'update']);
    // Papéis futuros: admin
    Route::delete('/areas-monitoradas/{area}', [AreaMonitoradaController::class, 'destroy']);
});
