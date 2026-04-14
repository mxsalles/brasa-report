<?php

use App\Models\AreaMonitorada;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('api areas monitoradas autentica com sessao web e referer stateful', function () {
    config([
        'app.url' => 'http://localhost',
        'sanctum.stateful' => explode(',', 'localhost,127.0.0.1,localhost:8000,127.0.0.1:8000'),
    ]);

    $usuario = Usuario::factory()->verified()->create();
    AreaMonitorada::factory()->create(['nome' => 'Pantanal Geral']);

    $baseUrl = rtrim((string) config('app.url'), '/');

    $response = $this->actingAs($usuario)
        ->withHeader('Referer', $baseUrl.'/registrar-incendio')
        ->getJson('/api/areas-monitoradas?nome='.rawurlencode('Pantanal Geral'));

    $response->assertOk();
    expect($response->json('data'))->not->toBeEmpty()
        ->and($response->json('data.0.nome'))->toBe('Pantanal Geral');
});
