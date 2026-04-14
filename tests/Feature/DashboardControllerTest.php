<?php

use App\Enums\StatusIncendio;
use App\Models\Alerta;
use App\Models\Incendio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function dashboardAuthHeaders(?Usuario $usuario = null): array
{
    $usuario ??= Usuario::factory()->create();

    return [
        'Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken,
        'Accept' => 'application/json',
    ];
}

test('test_dashboard_retorna_contagens_reais', function () {
    Http::fake();

    Incendio::factory()->count(3)->create();
    Alerta::factory()->count(2)->create();

    $response = $this->getJson('/api/dashboard', dashboardAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('incendios.total', 3)
        ->assertJsonPath('alertas.total', 2)
        ->assertJsonPath('incendios_recentes.0.id', fn ($value) => is_string($value) && $value !== '')
        ->assertJsonPath('alertas_recentes.0.id', fn ($value) => is_string($value) && $value !== '');
});

test('test_dashboard_conta_incendios_por_status', function () {
    Http::fake();

    Incendio::factory()->create(['status' => StatusIncendio::Ativo]);
    Incendio::factory()->create(['status' => StatusIncendio::Contido]);
    Incendio::factory()->create(['status' => StatusIncendio::Resolvido]);
    Incendio::factory()->create(['status' => StatusIncendio::Resolvido]);

    $response = $this->getJson('/api/dashboard', dashboardAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('incendios.ativos', 1)
        ->assertJsonPath('incendios.contidos', 1)
        ->assertJsonPath('incendios.resolvidos', 2)
        ->assertJsonPath('incendios.total', 4);
});

test('test_dashboard_conta_alertas_nao_entregues', function () {
    Http::fake();

    Alerta::factory()->count(2)->create(['entregue' => false]);
    Alerta::factory()->count(3)->create(['entregue' => true]);

    $response = $this->getJson('/api/dashboard', dashboardAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('alertas.nao_entregues', 2)
        ->assertJsonPath('alertas.total', 5);
});

test('test_dashboard_requer_autenticacao', function () {
    $this->getJson('/api/dashboard')->assertUnauthorized();
});

test('test_dashboard_inclui_clima_de_openmeteo', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'current' => [
                'temperature_2m' => 38.2,
                'relative_humidity_2m' => 22,
                'time' => '2026-04-14T12:00',
            ],
        ], 200),
    ]);

    $response = $this->getJson('/api/dashboard', dashboardAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('clima.temperatura_c', 38.2)
        ->assertJsonPath('clima.umidade_pct', 22)
        ->assertJsonPath('clima.atualizado_em', '2026-04-14T12:00');
});

test('test_dashboard_nao_quebra_se_openmeteo_falhar', function () {
    Http::fake([
        'api.open-meteo.com/*' => Http::response([], 500),
    ]);

    $response = $this->getJson('/api/dashboard', dashboardAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('clima', null);
});
