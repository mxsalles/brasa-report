<?php

use App\Enums\StatusIncendio;
use App\Models\Alerta;
use App\Models\Incendio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    Incendio::factory()->count(3)->create();
    Alerta::factory()->count(2)->create();

    $response = $this->getJson('/api/dashboard', dashboardAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('incendios.total', 3)
        ->assertJsonPath('alertas.total', 2);
});

test('test_dashboard_conta_incendios_por_status', function () {
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
