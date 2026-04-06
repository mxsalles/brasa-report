<?php

use App\Enums\NivelRiscoIncendio;
use App\Enums\StatusIncendio;
use App\Models\AreaMonitorada;
use App\Models\Incendio;
use App\Models\LocalCritico;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Support\Str;

function localCriticoAuthHeaders(?Usuario $usuario = null): array
{
    $usuario ??= Usuario::factory()->create();

    return [
        'Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken,
        'Accept' => 'application/json',
    ];
}

test('test_lista_locais_criticos_paginados', function () {
    LocalCritico::factory()->count(25)->create();

    $response = $this->getJson('/api/locais-criticos', localCriticoAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 25);
});

test('test_filtra_locais_por_tipo', function () {
    LocalCritico::factory()->create(['tipo' => 'escola']);
    LocalCritico::factory()->create(['tipo' => 'residencia']);

    $response = $this->getJson('/api/locais-criticos?tipo=escola', localCriticoAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.tipo'))->toBe('escola');
});

test('test_filtra_locais_por_nome', function () {
    LocalCritico::factory()->create(['nome' => 'Escola Municipal']);
    LocalCritico::factory()->create(['nome' => 'Residência Ribeirinha']);

    $response = $this->getJson('/api/locais-criticos?nome=escola', localCriticoAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.nome'))->toBe('Escola Municipal');
});

test('test_index_requer_autenticacao', function () {
    $this->getJson('/api/locais-criticos')->assertUnauthorized();
});

test('test_retorna_local_critico', function () {
    $local = LocalCritico::factory()->create([
        'tipo' => 'infraestrutura',
        'latitude' => -16.5,
        'longitude' => -56.75,
        'descricao' => 'Ponto sensível',
    ]);

    $response = $this->getJson('/api/locais-criticos/'.$local->id, localCriticoAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('data.id', $local->id)
        ->assertJsonPath('data.tipo', 'infraestrutura')
        ->assertJsonPath('data.latitude', ''.$local->latitude)
        ->assertJsonPath('data.longitude', ''.$local->longitude)
        ->assertJsonPath('data.descricao', 'Ponto sensível');
});

test('test_retorna_404_para_local_inexistente', function () {
    $id = (string) Str::uuid();

    $this->getJson('/api/locais-criticos/'.$id, localCriticoAuthHeaders())
        ->assertNotFound();
});

test('test_cria_local_critico_com_dados_validos', function () {
    $payload = [
        'nome' => 'Escola da Comunidade',
        'tipo' => 'escola',
        'latitude' => -16.5,
        'longitude' => -56.75,
        'descricao' => 'Próxima ao rio',
    ];

    $response = $this->postJson('/api/locais-criticos', $payload, localCriticoAuthHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.nome', 'Escola da Comunidade')
        ->assertJsonPath('data.tipo', 'escola');

    $this->assertDatabaseHas('locais_criticos', [
        'nome' => 'Escola da Comunidade',
        'tipo' => 'escola',
    ]);
});

test('test_retorna_422_com_tipo_invalido', function () {
    $this->postJson('/api/locais-criticos', [
        'nome' => 'X',
        'tipo' => 'posto',
        'latitude' => 0,
        'longitude' => 0,
    ], localCriticoAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['tipo']);
});

test('test_retorna_422_com_coordenadas_invalidas', function () {
    $this->postJson('/api/locais-criticos', [
        'nome' => 'X',
        'tipo' => 'residencia',
        'latitude' => 200,
        'longitude' => -200,
    ], localCriticoAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

test('test_registra_log_de_auditoria_na_criacao', function () {
    $usuario = Usuario::factory()->create();

    $this->postJson('/api/locais-criticos', [
        'nome' => 'Local Log',
        'tipo' => 'infraestrutura',
        'latitude' => -16.5,
        'longitude' => -56.75,
    ], localCriticoAuthHeaders($usuario))->assertCreated();

    $local = LocalCritico::query()->where('nome', 'Local Log')->first();

    expect($local)->not->toBeNull();

    $log = LogAuditoria::query()
        ->where('acao', 'criacao_local_critico')
        ->where('entidade_tipo', 'locais_criticos')
        ->where('entidade_id', $local->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});

test('test_atualiza_local_critico', function () {
    $local = LocalCritico::factory()->create(['nome' => 'Nome Antigo']);

    $this->putJson('/api/locais-criticos/'.$local->id, [
        'nome' => 'Nome Novo',
        'descricao' => 'Atualizado',
    ], localCriticoAuthHeaders())->assertOk()
        ->assertJsonPath('data.nome', 'Nome Novo')
        ->assertJsonPath('data.descricao', 'Atualizado');

    $local->refresh();
    expect($local->nome)->toBe('Nome Novo')
        ->and($local->descricao)->toBe('Atualizado');
});

test('test_update_retorna_404_para_local_inexistente', function () {
    $id = (string) Str::uuid();

    $this->putJson('/api/locais-criticos/'.$id, [
        'nome' => 'X',
    ], localCriticoAuthHeaders())->assertNotFound();
});

test('test_remove_local_critico_sem_incendios', function () {
    $local = LocalCritico::factory()->create();

    $this->deleteJson('/api/locais-criticos/'.$local->id, [], localCriticoAuthHeaders())
        ->assertNoContent();

    $this->assertDatabaseMissing('locais_criticos', ['id' => $local->id]);
});

test('test_retorna_409_ao_remover_local_com_incendios', function () {
    $usuario = Usuario::factory()->create();
    $area = AreaMonitorada::query()->create([
        'nome' => 'Área Teste',
        'caminho_geopackage' => null,
        'geometria_wkt' => null,
        'importado_em' => now(),
    ]);
    $local = LocalCritico::factory()->create();

    Incendio::query()->create([
        'latitude' => -16.5,
        'longitude' => -56.75,
        'usuario_id' => $usuario->id,
        'area_id' => $area->id,
        'local_critico_id' => $local->id,
        'nivel_risco' => NivelRiscoIncendio::Alto,
        'status' => StatusIncendio::Ativo,
    ]);

    $this->deleteJson('/api/locais-criticos/'.$local->id, [], localCriticoAuthHeaders())
        ->assertStatus(409)
        ->assertJsonStructure(['message']);

    $this->assertDatabaseHas('locais_criticos', ['id' => $local->id]);
});

test('test_registra_log_de_auditoria_na_remocao', function () {
    $usuario = Usuario::factory()->create();
    $local = LocalCritico::factory()->create();

    $this->deleteJson('/api/locais-criticos/'.$local->id, [], localCriticoAuthHeaders($usuario))
        ->assertNoContent();

    $log = LogAuditoria::query()
        ->where('acao', 'remocao_local_critico')
        ->where('entidade_tipo', 'locais_criticos')
        ->where('entidade_id', $local->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});
