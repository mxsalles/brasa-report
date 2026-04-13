<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');
    Route::get('mapa', fn () => Inertia::render('mapa'))->name('mapa');
    Route::get('registrar-incendio', fn () => Inertia::render('registrar-incendio'))->name('registrar-incendio');
    Route::get('alertas', fn () => Inertia::render('alertas'))->name('alertas');
    Route::get('brigadas', fn () => Inertia::render('brigadas'))->name('brigadas');
    Route::get('administracao', fn () => Inertia::render('administracao'))->name('administracao');
});

require __DIR__.'/settings.php';
