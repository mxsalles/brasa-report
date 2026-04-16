<?php

use App\Models\Usuario;

test('usuario com papel user acessa listagem de brigadas', function () {
    $usuario = Usuario::factory()->create();
    $token = $usuario->createToken('test')->plainTextToken;

    $this->getJson('/api/brigadas', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk();
});

test('usuario com papel user nao pode criar brigada', function () {
    $usuario = Usuario::factory()->create();
    $token = $usuario->createToken('test')->plainTextToken;

    $this->postJson('/api/brigadas', ['nome' => 'X', 'tipo' => 'Y'], [
        'Authorization' => 'Bearer '.$token,
    ])->assertForbidden();
});

test('gestor acessa listagem de brigadas', function () {
    $usuario = Usuario::factory()->gestor()->create();
    $token = $usuario->createToken('test')->plainTextToken;

    $this->getJson('/api/brigadas', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk();
});

test('usuario bloqueado nao acessa api autenticada', function () {
    $usuario = Usuario::factory()->brigadista()->create(['bloqueado' => true]);
    $token = $usuario->createToken('test')->plainTextToken;

    $this->getJson('/api/dashboard', [
        'Authorization' => 'Bearer '.$token,
    ])->assertForbidden();
});
