<?php

use App\Http\Controllers\AdministracaoController;
use App\Http\Controllers\BrigadasPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MapaPageController;
use App\Http\Controllers\RegistrarIncendioController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified', 'nao-bloqueado'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('mapa', MapaPageController::class)->name('mapa');
    Route::get('registrar-incendio', RegistrarIncendioController::class)->name('registrar-incendio');
    Route::get('alertas', fn () => Inertia::render('alertas'))->name('alertas');
    Route::get('brigadas', [BrigadasPageController::class, 'index'])->name('brigadas');
    Route::get('administracao', [AdministracaoController::class, 'index'])
        ->middleware('funcao:gestor|administrador')
        ->name('administracao');
});

require __DIR__.'/settings.php';
