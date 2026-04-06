<?php

use App\Http\Controllers\AreaMonitoradaController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrigadaController;
use App\Http\Controllers\DeteccaoSateliteController;
use App\Http\Controllers\IncendioController;
use App\Http\Controllers\LocalCriticoController;
use App\Http\Controllers\UsuarioController;
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

    // Papéis futuros: gestor, admin
    Route::get('/locais-criticos', [LocalCriticoController::class, 'index']);
    // Papéis futuros: admin
    Route::post('/locais-criticos', [LocalCriticoController::class, 'store']);
    // Papéis futuros: gestor, admin
    Route::get('/locais-criticos/{local}', [LocalCriticoController::class, 'show']);
    // Papéis futuros: admin
    Route::put('/locais-criticos/{local}', [LocalCriticoController::class, 'update']);
    // Papéis futuros: admin
    Route::delete('/locais-criticos/{local}', [LocalCriticoController::class, 'destroy']);

    // Papéis futuros: admin
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    // Papéis futuros: admin
    Route::post('/usuarios', [UsuarioController::class, 'store']);
    // Papéis futuros: admin
    Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show']);
    // Papéis futuros: admin
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update']);
    // Papéis futuros: admin
    Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy']);
    // Papéis futuros: admin
    Route::patch('/usuarios/{usuario}/funcao', [UsuarioController::class, 'atualizarFuncao']);
    // Papéis futuros: admin, gestor
    Route::patch('/usuarios/{usuario}/brigada', [UsuarioController::class, 'atualizarBrigada']);

    // Papéis futuros: gestor, admin
    Route::get('/deteccoes-satelite', [DeteccaoSateliteController::class, 'index']);
    // Papéis futuros: admin
    Route::post('/deteccoes-satelite', [DeteccaoSateliteController::class, 'store']);
    // Papéis futuros: admin
    Route::post('/deteccoes-satelite/lote', [DeteccaoSateliteController::class, 'storeLote']);
    // Papéis futuros: gestor, admin
    Route::get('/deteccoes-satelite/{deteccao}', [DeteccaoSateliteController::class, 'show']);

    // Papéis futuros: brigadista, gestor, admin
    Route::get('/incendios', [IncendioController::class, 'index']);
    // Papéis futuros: brigadista, gestor, admin
    Route::post('/incendios', [IncendioController::class, 'store']);
    // Papéis futuros: brigadista, gestor, admin
    Route::get('/incendios/{incendio}', [IncendioController::class, 'show']);
    // Papéis futuros: gestor, admin
    Route::put('/incendios/{incendio}', [IncendioController::class, 'update']);
    // Papéis futuros: brigadista, gestor, admin
    Route::patch('/incendios/{incendio}/status', [IncendioController::class, 'atualizarStatus']);
    // Papéis futuros: gestor, admin
    Route::patch('/incendios/{incendio}/risco', [IncendioController::class, 'atualizarRisco']);
});
