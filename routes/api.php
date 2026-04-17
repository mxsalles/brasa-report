<?php

use App\Http\Controllers\AlertaController;
use App\Http\Controllers\AreaMonitoradaController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrigadaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DespachoBrigadaController;
use App\Http\Controllers\DeteccaoSateliteController;
use App\Http\Controllers\IncendioController;
use App\Http\Controllers\LeituraMeteorologicaController;
use App\Http\Controllers\LocalCriticoController;
use App\Http\Controllers\LogAuditoriaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::post('/auth/senha/esqueci', [PasswordResetController::class, 'enviarToken']);
Route::post('/auth/senha/redefinir', [PasswordResetController::class, 'redefinir']);

Route::middleware(['auth:sanctum', 'nao-bloqueado'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::middleware('funcao:user|brigadista|gestor|administrador')->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'dados']);
        Route::get('/brigadas', [BrigadaController::class, 'index']);
        Route::get('/brigadas/{brigada}', [BrigadaController::class, 'show']);
    });

    Route::middleware('funcao:gestor|administrador')->group(function (): void {
        Route::post('/brigadas', [BrigadaController::class, 'store']);
        Route::put('/brigadas/{brigada}', [BrigadaController::class, 'update']);
        Route::delete('/brigadas/{brigada}', [BrigadaController::class, 'destroy']);
        Route::post('/brigadas/{id}/restore', [BrigadaController::class, 'restore'])->whereUuid('id');

        Route::get('/areas-monitoradas', [AreaMonitoradaController::class, 'index']);
        Route::get('/areas-monitoradas/{area}', [AreaMonitoradaController::class, 'show']);

        Route::get('/locais-criticos', [LocalCriticoController::class, 'index']);
        Route::get('/locais-criticos/{local}', [LocalCriticoController::class, 'show']);

        Route::get('/deteccoes-satelite', [DeteccaoSateliteController::class, 'index']);
        Route::get('/deteccoes-satelite/{deteccao}', [DeteccaoSateliteController::class, 'show']);
    });

    Route::middleware('funcao:administrador')->group(function (): void {

        Route::post('/areas-monitoradas', [AreaMonitoradaController::class, 'store']);
        Route::put('/areas-monitoradas/{area}', [AreaMonitoradaController::class, 'update']);
        Route::delete('/areas-monitoradas/{area}', [AreaMonitoradaController::class, 'destroy']);

        Route::post('/locais-criticos', [LocalCriticoController::class, 'store']);
        Route::put('/locais-criticos/{local}', [LocalCriticoController::class, 'update']);
        Route::delete('/locais-criticos/{local}', [LocalCriticoController::class, 'destroy']);

        Route::post('/deteccoes-satelite', [DeteccaoSateliteController::class, 'store']);
        Route::post('/deteccoes-satelite/lote', [DeteccaoSateliteController::class, 'storeLote']);

        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::post('/usuarios', [UsuarioController::class, 'store']);
        Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show']);
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update']);
        Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy']);
        Route::post('/usuarios/{id}/restore', [UsuarioController::class, 'restore'])->whereUuid('id');

        Route::get('/logs-auditoria', [LogAuditoriaController::class, 'index']);
        Route::get('/logs-auditoria/{log}', [LogAuditoriaController::class, 'show']);
    });

    Route::middleware('funcao:gestor|administrador')->group(function (): void {
        Route::patch('/usuarios/{usuario}/funcao', [UsuarioController::class, 'atualizarFuncao']);
        Route::patch('/usuarios/{usuario}/brigada', [UsuarioController::class, 'atualizarBrigada']);
        Route::patch('/usuarios/{usuario}/bloqueio', [UsuarioController::class, 'alternarBloqueio']);
    });

    Route::middleware('funcao:brigadista|gestor|administrador')->group(function (): void {
        Route::patch('/brigadas/{brigada}/localizacao', [BrigadaController::class, 'atualizarLocalizacao']);

        Route::get('/incendios', [IncendioController::class, 'index']);
        Route::post('/incendios', [IncendioController::class, 'store']);
        Route::get('/incendios/{incendio}', [IncendioController::class, 'show']);
        Route::get('/incendios/{incendio}/historico', [IncendioController::class, 'historico']);

        Route::get('/incendios/{incendio}/leituras', [LeituraMeteorologicaController::class, 'index']);
        Route::post('/incendios/{incendio}/leituras', [LeituraMeteorologicaController::class, 'store']);
        Route::get('/incendios/{incendio}/leituras/{leitura}', [LeituraMeteorologicaController::class, 'show']);

        Route::get('/incendios/{incendio}/despachos', [DespachoBrigadaController::class, 'index']);
        Route::get('/incendios/{incendio}/despachos/{despacho}', [DespachoBrigadaController::class, 'show']);

        Route::get('/alertas', [AlertaController::class, 'index']);
        Route::get('/alertas/{alerta}', [AlertaController::class, 'show']);
        Route::patch('/alertas/{alerta}/entregue', [AlertaController::class, 'marcarEntregue']);
    });

    Route::middleware('funcao:gestor|administrador')->group(function (): void {
        Route::put('/incendios/{incendio}', [IncendioController::class, 'update']);
        Route::delete('/incendios/{incendio}', [IncendioController::class, 'destroy']);
        Route::patch('/incendios/{incendio}/status', [IncendioController::class, 'atualizarStatus']);
        Route::patch('/incendios/{incendio}/risco', [IncendioController::class, 'atualizarRisco']);
        Route::post('/incendios/{id}/restore', [IncendioController::class, 'restore'])->whereUuid('id');

        Route::post('/incendios/{incendio}/despachos', [DespachoBrigadaController::class, 'store']);
        Route::patch('/incendios/{incendio}/despachos/{despacho}/chegada', [DespachoBrigadaController::class, 'registrarChegada']);
        Route::patch('/incendios/{incendio}/despachos/{despacho}/finalizar', [DespachoBrigadaController::class, 'finalizar']);
    });
});
