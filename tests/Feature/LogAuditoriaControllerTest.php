<?php

use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function usuarioAuthHeadersLogAuditoria(?Usuario $usuario = null): array
{
    $usuario ??= Usuario::factory()->administrador()->create();

    return [
        'Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken,
    ];
}

test('test_lista_logs_paginados', function () {
    LogAuditoria::factory()->count(60)->create();

    $response = $this->getJson('/api/logs-auditoria', usuarioAuthHeadersLogAuditoria());

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 50)
        ->assertJsonPath('meta.total', 60);
});

test('test_filtra_por_acao', function () {
    LogAuditoria::factory()->create(['acao' => 'login']);
    LogAuditoria::factory()->create(['acao' => 'logout']);

    $response = $this->getJson('/api/logs-auditoria?acao=log', usuarioAuthHeadersLogAuditoria());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('test_filtra_por_entidade_tipo', function () {
    LogAuditoria::factory()->create(['entidade_tipo' => 'incendios']);
    LogAuditoria::factory()->create(['entidade_tipo' => 'usuarios']);

    $response = $this->getJson('/api/logs-auditoria?entidade_tipo=incendios', usuarioAuthHeadersLogAuditoria());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.entidade_tipo'))->toBe('incendios');
});

test('test_filtra_por_entidade_id', function () {
    $alvoId = (string) Str::uuid();
    LogAuditoria::factory()->create(['entidade_id' => $alvoId]);
    LogAuditoria::factory()->create(['entidade_id' => (string) Str::uuid()]);

    $response = $this->getJson('/api/logs-auditoria?entidade_id='.$alvoId, usuarioAuthHeadersLogAuditoria());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.entidade_id'))->toBe($alvoId);
});

test('test_filtra_por_usuario_id', function () {
    $usuario = Usuario::factory()->create();
    LogAuditoria::factory()->create(['usuario_id' => $usuario->id]);
    LogAuditoria::factory()->create(['usuario_id' => Usuario::factory()]);

    $response = $this->getJson('/api/logs-auditoria?usuario_id='.$usuario->id, usuarioAuthHeadersLogAuditoria());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.usuario_id'))->toBe($usuario->id);
});

test('test_filtra_por_intervalo_de_data', function () {
    $idDentro = (string) Str::uuid();
    $idFora = (string) Str::uuid();

    DB::table('logs_auditoria')->insert([
        [
            'id' => $idDentro,
            'usuario_id' => null,
            'acao' => 'login',
            'entidade_tipo' => 'usuarios',
            'entidade_id' => (string) Str::uuid(),
            'dados_json' => null,
            'criado_em' => '2024-06-01 12:00:00+00',
            'atualizado_em' => '2024-06-01 12:00:00+00',
        ],
        [
            'id' => $idFora,
            'usuario_id' => null,
            'acao' => 'login',
            'entidade_tipo' => 'usuarios',
            'entidade_id' => (string) Str::uuid(),
            'dados_json' => null,
            'criado_em' => '2025-01-01 12:00:00+00',
            'atualizado_em' => '2025-01-01 12:00:00+00',
        ],
    ]);

    $response = $this->getJson('/api/logs-auditoria?de=2024-01-01&ate=2024-12-31', usuarioAuthHeadersLogAuditoria());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($idDentro);
});

test('test_index_requer_autenticacao', function () {
    $this->getJson('/api/logs-auditoria')->assertUnauthorized();
});

test('test_retorna_log_com_usuario', function () {
    $usuario = Usuario::factory()->create();
    $id = (string) Str::uuid();

    DB::table('logs_auditoria')->insert([
        'id' => $id,
        'usuario_id' => $usuario->id,
        'acao' => 'login',
        'entidade_tipo' => 'usuarios',
        'entidade_id' => $usuario->id,
        'dados_json' => json_encode(['ok' => true]),
        'criado_em' => now(),
        'atualizado_em' => now(),
    ]);

    $response = $this->getJson('/api/logs-auditoria/'.$id, usuarioAuthHeadersLogAuditoria());

    $response->assertOk()
        ->assertJsonPath('data.id', $id)
        ->assertJsonPath('data.usuario_id', $usuario->id)
        ->assertJsonPath('data.usuario.id', $usuario->id)
        ->assertJsonPath('data.dados_json.ok', true);
});

test('test_retorna_404_para_log_inexistente', function () {
    $id = (string) Str::uuid();

    $this->getJson('/api/logs-auditoria/'.$id, usuarioAuthHeadersLogAuditoria())
        ->assertNotFound();
});
