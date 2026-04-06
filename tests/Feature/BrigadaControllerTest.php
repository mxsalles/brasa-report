<?php

use App\Models\Brigada;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Support\Str;

function brigadaAuthHeaders(?Usuario $usuario = null): array
{
    $usuario ??= Usuario::factory()->create();

    return [
        'Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken,
    ];
}

test('test_lista_brigadas_paginadas', function () {
    Brigada::factory()->count(25)->create();

    $response = $this->getJson('/api/brigadas', brigadaAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 25);
});

test('test_filtra_brigadas_por_disponibilidade', function () {
    Brigada::factory()->create(['disponivel' => true]);
    Brigada::factory()->create(['disponivel' => false]);

    $response = $this->getJson('/api/brigadas?disponivel=true', brigadaAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.disponivel'))->toBeTrue();
});

test('test_index_requer_autenticacao', function () {
    $this->getJson('/api/brigadas')->assertUnauthorized();
});

test('test_retorna_brigada_com_membros', function () {
    $brigada = Brigada::factory()->create();
    $membro = Usuario::factory()->create(['brigada_id' => $brigada->id]);

    $response = $this->getJson('/api/brigadas/'.$brigada->id, brigadaAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('data.id', $brigada->id)
        ->assertJsonPath('data.membros.0.id', $membro->id)
        ->assertJsonPath('data.membros.0.nome', $membro->nome)
        ->assertJsonPath('data.membros.0.funcao', $membro->funcao->value)
        ->assertJsonMissingPath('data.membros.0.email');
});

test('test_retorna_404_para_brigada_inexistente', function () {
    $id = (string) Str::uuid();

    $this->getJson('/api/brigadas/'.$id, brigadaAuthHeaders())
        ->assertNotFound();
});

test('test_cria_brigada_com_dados_validos', function () {
    $payload = [
        'nome' => 'Brigada Pantanal Norte',
        'tipo' => 'florestal',
        'latitude_atual' => -16.5,
        'longitude_atual' => -56.75,
        'disponivel' => true,
    ];

    $response = $this->postJson('/api/brigadas', $payload, brigadaAuthHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.nome', 'Brigada Pantanal Norte')
        ->assertJsonPath('data.tipo', 'florestal');

    $this->assertDatabaseHas('brigadas', [
        'nome' => 'Brigada Pantanal Norte',
        'tipo' => 'florestal',
    ]);
});

test('test_retorna_422_com_dados_invalidos', function () {
    $this->postJson('/api/brigadas', [], brigadaAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nome', 'tipo']);
});

test('test_registra_log_de_auditoria_na_criacao', function () {
    $usuario = Usuario::factory()->create();

    $this->postJson('/api/brigadas', [
        'nome' => 'Nova Brigada',
        'tipo' => 'urbano',
    ], brigadaAuthHeaders($usuario))->assertCreated();

    $brigada = Brigada::query()->where('nome', 'Nova Brigada')->first();

    expect($brigada)->not->toBeNull();

    $log = LogAuditoria::query()
        ->where('acao', 'criacao_brigada')
        ->where('entidade_tipo', 'brigadas')
        ->where('entidade_id', $brigada->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});

test('test_atualiza_brigada_com_dados_validos', function () {
    $brigada = Brigada::factory()->create(['nome' => 'Nome Antigo']);

    $this->putJson('/api/brigadas/'.$brigada->id, [
        'nome' => 'Nome Novo',
    ], brigadaAuthHeaders())->assertOk()
        ->assertJsonPath('data.nome', 'Nome Novo');

    expect($brigada->fresh()->nome)->toBe('Nome Novo');
});

test('test_update_retorna_404_para_brigada_inexistente', function () {
    $id = (string) Str::uuid();

    $this->putJson('/api/brigadas/'.$id, [
        'nome' => 'X',
    ], brigadaAuthHeaders())->assertNotFound();
});

test('test_remove_brigada_sem_membros', function () {
    $brigada = Brigada::factory()->create();

    $this->deleteJson('/api/brigadas/'.$brigada->id, [], brigadaAuthHeaders())
        ->assertNoContent();

    $this->assertDatabaseMissing('brigadas', ['id' => $brigada->id]);
});

test('test_retorna_409_ao_remover_brigada_com_membros', function () {
    $brigada = Brigada::factory()->create();
    Usuario::factory()->create(['brigada_id' => $brigada->id]);

    $this->deleteJson('/api/brigadas/'.$brigada->id, [], brigadaAuthHeaders())
        ->assertStatus(409)
        ->assertJsonStructure(['message']);

    $this->assertDatabaseHas('brigadas', ['id' => $brigada->id]);
});

test('test_registra_log_de_auditoria_na_remocao', function () {
    $usuario = Usuario::factory()->create();
    $brigada = Brigada::factory()->create();

    $this->deleteJson('/api/brigadas/'.$brigada->id, [], brigadaAuthHeaders($usuario))
        ->assertNoContent();

    $log = LogAuditoria::query()
        ->where('acao', 'remocao_brigada')
        ->where('entidade_tipo', 'brigadas')
        ->where('entidade_id', $brigada->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});

test('test_atualiza_coordenadas_da_brigada', function () {
    $brigada = Brigada::factory()->create([
        'latitude_atual' => null,
        'longitude_atual' => null,
    ]);

    $this->patchJson('/api/brigadas/'.$brigada->id.'/localizacao', [
        'latitude_atual' => -15.1234567,
        'longitude_atual' => -55.9876543,
    ], brigadaAuthHeaders())->assertOk();

    $brigada->refresh();
    expect((float) $brigada->latitude_atual)->toBe(-15.1234567)
        ->and((float) $brigada->longitude_atual)->toBe(-55.9876543);
});

test('test_retorna_422_com_coordenadas_invalidas', function () {
    $brigada = Brigada::factory()->create();

    $this->patchJson('/api/brigadas/'.$brigada->id.'/localizacao', [
        'latitude_atual' => 200,
        'longitude_atual' => 0,
    ], brigadaAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude_atual']);
});
