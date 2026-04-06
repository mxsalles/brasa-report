<?php

use App\Enums\NivelRiscoIncendio;
use App\Enums\StatusIncendio;
use App\Models\AreaMonitorada;
use App\Models\Incendio;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function incendioAuthHeaders(?Usuario $usuario = null): array
{
    $usuario ??= Usuario::factory()->create();

    return [
        'Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken,
        'Accept' => 'application/json',
    ];
}

test('test_lista_incendios_paginados', function () {
    Incendio::factory()->count(25)->create();

    $response = $this->getJson('/api/incendios', incendioAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 25);
});

test('test_filtra_por_status', function () {
    Incendio::factory()->create(['status' => StatusIncendio::Ativo]);
    Incendio::factory()->create(['status' => StatusIncendio::Resolvido]);

    $response = $this->getJson('/api/incendios?status=ativo', incendioAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.status'))->toBe('ativo');
});

test('test_filtra_por_nivel_risco', function () {
    Incendio::factory()->create(['nivel_risco' => NivelRiscoIncendio::Alto]);
    Incendio::factory()->create(['nivel_risco' => NivelRiscoIncendio::Baixo]);

    $response = $this->getJson('/api/incendios?nivel_risco=alto', incendioAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.nivel_risco'))->toBe('alto');
});

test('test_filtra_por_area', function () {
    $areaA = AreaMonitorada::factory()->create();
    $areaB = AreaMonitorada::factory()->create();
    Incendio::factory()->create(['area_id' => $areaA->id]);
    Incendio::factory()->create(['area_id' => $areaB->id]);

    $response = $this->getJson('/api/incendios?area_id='.$areaA->id, incendioAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.area_id'))->toBe($areaA->id);
});

test('test_filtra_por_intervalo_de_data', function () {
    Incendio::factory()->create(['detectado_em' => '2024-06-15 12:00:00']);
    Incendio::factory()->create(['detectado_em' => '2025-06-15 12:00:00']);

    $response = $this->getJson('/api/incendios?de=2024-01-01&ate=2024-12-31', incendioAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

test('test_index_requer_autenticacao', function () {
    $this->getJson('/api/incendios')->assertUnauthorized();
});

test('test_retorna_incendio_com_relacionamentos', function () {
    $incendio = Incendio::factory()->create();

    $response = $this->getJson('/api/incendios/'.$incendio->id, incendioAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('data.id', $incendio->id)
        ->assertJsonStructure([
            'data' => [
                'area' => ['id', 'nome'],
                'usuario' => ['id', 'nome', 'email', 'funcao'],
            ],
        ]);
});

test('test_retorna_404_para_incendio_inexistente', function () {
    $id = (string) Str::uuid();

    $this->getJson('/api/incendios/'.$id, incendioAuthHeaders())
        ->assertNotFound();
});

test('test_registra_incendio_com_dados_validos', function () {
    $usuario = Usuario::factory()->create();
    $area = AreaMonitorada::factory()->create();

    $response = $this->postJson('/api/incendios', [
        'latitude' => -16.5,
        'longitude' => -56.75,
        'detectado_em' => '2024-03-10T14:00:00Z',
        'nivel_risco' => 'medio',
        'area_id' => $area->id,
    ], incendioAuthHeaders($usuario));

    $response->assertCreated()
        ->assertJsonPath('data.latitude', '-16.5000000')
        ->assertJsonPath('data.nivel_risco', 'medio')
        ->assertJsonPath('data.status', 'ativo');
});

test('test_usuario_id_preenchido_automaticamente', function () {
    $autenticado = Usuario::factory()->create();
    $outro = Usuario::factory()->create();
    $area = AreaMonitorada::factory()->create();

    $response = $this->postJson('/api/incendios', [
        'latitude' => -16.5,
        'longitude' => -56.75,
        'detectado_em' => '2024-03-10T14:00:00Z',
        'nivel_risco' => 'alto',
        'area_id' => $area->id,
        'usuario_id' => $outro->id,
    ], incendioAuthHeaders($autenticado));

    $response->assertCreated()
        ->assertJsonPath('data.usuario_id', $autenticado->id);
});

test('test_status_inicial_sempre_ativo', function () {
    $usuario = Usuario::factory()->create();
    $area = AreaMonitorada::factory()->create();

    $response = $this->postJson('/api/incendios', [
        'latitude' => -16.5,
        'longitude' => -56.75,
        'detectado_em' => '2024-03-10T14:00:00Z',
        'nivel_risco' => 'alto',
        'area_id' => $area->id,
        'status' => 'resolvido',
    ], incendioAuthHeaders($usuario));

    $response->assertCreated()
        ->assertJsonPath('data.status', 'ativo');
});

test('test_retorna_422_sem_area_id', function () {
    $usuario = Usuario::factory()->create();

    $this->postJson('/api/incendios', [
        'latitude' => -16.5,
        'longitude' => -56.75,
        'detectado_em' => '2024-03-10T14:00:00Z',
        'nivel_risco' => 'alto',
    ], incendioAuthHeaders($usuario))
        ->assertUnprocessable();
});

test('test_retorna_422_com_area_inexistente', function () {
    $usuario = Usuario::factory()->create();
    $areaId = (string) Str::uuid();

    $this->postJson('/api/incendios', [
        'latitude' => -16.5,
        'longitude' => -56.75,
        'detectado_em' => '2024-03-10T14:00:00Z',
        'nivel_risco' => 'alto',
        'area_id' => $areaId,
    ], incendioAuthHeaders($usuario))
        ->assertUnprocessable();
});

test('test_registra_log_de_auditoria_no_registro', function () {
    $usuario = Usuario::factory()->create();
    $area = AreaMonitorada::factory()->create();

    $response = $this->postJson('/api/incendios', [
        'latitude' => -16.5,
        'longitude' => -56.75,
        'detectado_em' => '2024-03-10T14:00:00Z',
        'nivel_risco' => 'alto',
        'area_id' => $area->id,
    ], incendioAuthHeaders($usuario));

    $id = $response->json('data.id');

    $log = LogAuditoria::query()
        ->where('acao', 'registro_incendio')
        ->where('entidade_tipo', 'incendios')
        ->where('entidade_id', $id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});

test('test_atualiza_dados_do_incendio', function () {
    $incendio = Incendio::factory()->create([
        'latitude' => -10.0,
        'longitude' => -50.0,
        'nivel_risco' => NivelRiscoIncendio::Baixo,
    ]);

    $this->putJson('/api/incendios/'.$incendio->id, [
        'latitude' => -16.5,
        'longitude' => -56.75,
        'nivel_risco' => 'alto',
    ], incendioAuthHeaders())
        ->assertOk()
        ->assertJsonPath('data.latitude', '-16.5000000')
        ->assertJsonPath('data.nivel_risco', 'alto');
});

test('test_update_nao_altera_status', function () {
    $incendio = Incendio::factory()->create([
        'status' => StatusIncendio::Ativo,
    ]);

    $this->putJson('/api/incendios/'.$incendio->id, [
        'latitude' => -16.5,
        'status' => 'resolvido',
    ], incendioAuthHeaders())
        ->assertOk();

    expect($incendio->fresh()->status)->toBe(StatusIncendio::Ativo);
});

test('test_update_nao_altera_usuario_id', function () {
    $dono = Usuario::factory()->create();
    $outro = Usuario::factory()->create();
    $incendio = Incendio::factory()->create(['usuario_id' => $dono->id]);

    $this->putJson('/api/incendios/'.$incendio->id, [
        'latitude' => -16.5,
        'usuario_id' => $outro->id,
    ], incendioAuthHeaders())
        ->assertOk();

    expect($incendio->fresh()->usuario_id)->toBe($dono->id);
});

test('test_update_retorna_404_para_incendio_inexistente', function () {
    $id = (string) Str::uuid();

    $this->putJson('/api/incendios/'.$id, [
        'latitude' => -16.5,
    ], incendioAuthHeaders())
        ->assertNotFound();
});

test('test_atualiza_status_do_incendio', function () {
    $incendio = Incendio::factory()->create(['status' => StatusIncendio::Ativo]);

    $this->patchJson('/api/incendios/'.$incendio->id.'/status', [
        'status' => 'contido',
    ], incendioAuthHeaders())
        ->assertOk()
        ->assertJsonPath('data.status', 'contido');

    expect($incendio->fresh()->status)->toBe(StatusIncendio::Contido);
});

test('test_registra_status_anterior_e_novo_no_log', function () {
    $autor = Usuario::factory()->create();
    $incendio = Incendio::factory()->create(['status' => StatusIncendio::Ativo]);

    $this->patchJson('/api/incendios/'.$incendio->id.'/status', [
        'status' => 'resolvido',
    ], incendioAuthHeaders($autor))
        ->assertOk();

    $log = LogAuditoria::query()
        ->where('acao', 'atualizacao_status_incendio')
        ->where('entidade_tipo', 'incendios')
        ->where('entidade_id', $incendio->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->dados_json)->toHaveKeys(['status_anterior', 'status_novo'])
        ->and($log->dados_json['status_anterior'])->toBe('ativo')
        ->and($log->dados_json['status_novo'])->toBe('resolvido')
        ->and($log->usuario_id)->toBe($autor->id);
});

test('test_retorna_422_com_status_invalido', function () {
    $incendio = Incendio::factory()->create();

    $this->patchJson('/api/incendios/'.$incendio->id.'/status', [
        'status' => 'invalido',
    ], incendioAuthHeaders())
        ->assertUnprocessable();
});

test('test_atualiza_nivel_de_risco', function () {
    $incendio = Incendio::factory()->create(['nivel_risco' => NivelRiscoIncendio::Alto]);

    $this->patchJson('/api/incendios/'.$incendio->id.'/risco', [
        'nivel_risco' => 'baixo',
    ], incendioAuthHeaders())
        ->assertOk()
        ->assertJsonPath('data.nivel_risco', 'baixo');

    expect($incendio->fresh()->nivel_risco)->toBe(NivelRiscoIncendio::Baixo);
});

test('test_registra_risco_anterior_e_novo_no_log', function () {
    $autor = Usuario::factory()->create();
    $incendio = Incendio::factory()->create(['nivel_risco' => NivelRiscoIncendio::Medio]);

    $this->patchJson('/api/incendios/'.$incendio->id.'/risco', [
        'nivel_risco' => 'alto',
    ], incendioAuthHeaders($autor))
        ->assertOk();

    $log = LogAuditoria::query()
        ->where('acao', 'atualizacao_risco_incendio')
        ->where('entidade_tipo', 'incendios')
        ->where('entidade_id', $incendio->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->dados_json)->toHaveKeys(['nivel_risco_anterior', 'nivel_risco_novo'])
        ->and($log->dados_json['nivel_risco_anterior'])->toBe('medio')
        ->and($log->dados_json['nivel_risco_novo'])->toBe('alto')
        ->and($log->usuario_id)->toBe($autor->id);
});

test('test_retorna_422_com_risco_invalido', function () {
    $incendio = Incendio::factory()->create();

    $this->patchJson('/api/incendios/'.$incendio->id.'/risco', [
        'nivel_risco' => 'critico',
    ], incendioAuthHeaders())
        ->assertUnprocessable();
});
