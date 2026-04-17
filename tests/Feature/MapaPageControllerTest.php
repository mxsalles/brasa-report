<?php

use App\Enums\FuncaoUsuario;
use App\Enums\StatusIncendio;
use App\Models\Incendio;
use App\Models\Usuario;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('usuario autenticado acessa pagina do mapa', function () {
    $usuario = Usuario::factory()->verified()->create(['funcao' => FuncaoUsuario::User]);

    $this->actingAs($usuario)
        ->get(route('mapa'))
        ->assertOk();
});

test('visitante nao autenticado e redirecionado para login', function () {
    $this->get(route('mapa'))
        ->assertRedirect(route('login'));
});

test('todos os incendios aparecem nas props incluindo resolvidos', function () {
    $usuario = Usuario::factory()->verified()->create();
    Incendio::factory()->create(['status' => StatusIncendio::Ativo]);
    Incendio::factory()->create(['status' => StatusIncendio::EmCombate]);
    Incendio::factory()->create(['status' => StatusIncendio::Contido]);
    Incendio::factory()->create(['status' => StatusIncendio::Resolvido]);

    $response = $this->actingAs($usuario)->get(route('mapa'));
    $props = $response->original->getData()['page']['props'];

    expect($props['incendios'])->toHaveCount(4);
});

test('incendios incluem campos esperados', function () {
    $usuario = Usuario::factory()->verified()->create();
    $incendio = Incendio::factory()->create(['status' => StatusIncendio::Ativo]);

    $response = $this->actingAs($usuario)->get(route('mapa'));
    $props = $response->original->getData()['page']['props'];

    $item = $props['incendios'][0];

    expect($item)->toHaveKeys([
        'id', 'latitude', 'longitude', 'status', 'nivel_risco',
        'detectado_em', 'area_nome', 'local_critico_nome',
    ])
        ->and($item['id'])->toBe($incendio->id)
        ->and($item['status'])->toBe('ativo');
});

test('podeGerenciar e true para gestor', function () {
    $gestor = Usuario::factory()->verified()->gestor()->create();

    $response = $this->actingAs($gestor)->get(route('mapa'));
    $props = $response->original->getData()['page']['props'];

    expect($props['podeGerenciar'])->toBeTrue();
});

test('podeGerenciar e false para user', function () {
    $user = Usuario::factory()->verified()->create(['funcao' => FuncaoUsuario::User]);

    $response = $this->actingAs($user)->get(route('mapa'));
    $props = $response->original->getData()['page']['props'];

    expect($props['podeGerenciar'])->toBeFalse();
});

test('condicoesClimaticas refletem openmeteo como no dashboard', function () {
    Cache::flush();
    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'current' => [
                'temperature_2m' => 35.5,
                'relative_humidity_2m' => 20,
                'time' => '2026-04-14T15:00',
            ],
        ], 200),
    ]);

    $usuario = Usuario::factory()->verified()->create();

    $response = $this->actingAs($usuario)->get(route('mapa'));
    $props = $response->original->getData()['page']['props'];

    expect($props['condicoesClimaticas'])->not->toBeNull()
        ->and($props['condicoesClimaticas'])->toHaveKeys(['temperatura_c', 'umidade_pct', 'atualizado_em'])
        ->and($props['condicoesClimaticas']['temperatura_c'])->toBe(35.5)
        ->and($props['condicoesClimaticas']['umidade_pct'])->toBe(20)
        ->and($props['condicoesClimaticas']['atualizado_em'])->toBe('2026-04-14T15:00');
});

test('condicoesClimaticas e null quando openmeteo falha', function () {
    Cache::flush();
    Http::fake([
        'api.open-meteo.com/*' => Http::response([], 500),
    ]);

    $usuario = Usuario::factory()->verified()->create();

    $response = $this->actingAs($usuario)->get(route('mapa'));
    $props = $response->original->getData()['page']['props'];

    expect($props['condicoesClimaticas'])->toBeNull();
});

test('incendios soft deleted nao aparecem no mapa', function () {
    $usuario = Usuario::factory()->verified()->create();
    $visivel = Incendio::factory()->create(['status' => StatusIncendio::Ativo]);
    $removido = Incendio::factory()->create(['status' => StatusIncendio::Ativo]);
    $removido->delete();

    $response = $this->actingAs($usuario)->get(route('mapa'));
    $props = $response->original->getData()['page']['props'];

    $ids = collect($props['incendios'])->pluck('id')->all();

    expect($ids)->toContain($visivel->id)
        ->and($ids)->not->toContain($removido->id);
});

test('usuario bloqueado nao acessa pagina do mapa', function () {
    $usuario = Usuario::factory()->verified()->bloqueado()->create();

    $this->actingAs($usuario)
        ->get(route('mapa'))
        ->assertForbidden();
});
