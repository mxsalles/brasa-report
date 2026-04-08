<?php

use App\Models\Brigada;
use App\Models\DespachoBrigada;
use App\Models\Incendio;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function despachoBrigadaAuthHeaders(?Usuario $usuario = null): array
{
    $usuario ??= Usuario::factory()->create();

    return [
        'Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken,
        'Accept' => 'application/json',
    ];
}

test('test_lista_despachos_do_incendio', function () {
    $incendio = Incendio::factory()->create();
    DespachoBrigada::factory()->count(3)->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => now()->subHours(3),
    ]);

    $response = $this->getJson('/api/incendios/'.$incendio->id.'/despachos', despachoBrigadaAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 3);
    expect($response->json('data'))->toHaveCount(3);
});

test('test_filtra_despachos_finalizados', function () {
    $incendio = Incendio::factory()->create();
    DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => now()->subDay(),
        'chegada_em' => now()->subDay()->addHour(),
        'finalizado_em' => now()->subHours(20),
    ]);
    DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => now()->subHour(),
        'finalizado_em' => null,
    ]);

    $response = $this->getJson(
        '/api/incendios/'.$incendio->id.'/despachos?finalizado=true',
        despachoBrigadaAuthHeaders()
    );

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.finalizado_em'))->not->toBeNull();
});

test('test_filtra_despachos_em_aberto', function () {
    $incendio = Incendio::factory()->create();
    DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => now()->subDay(),
        'chegada_em' => now()->subDay()->addHour(),
        'finalizado_em' => now()->subHours(20),
    ]);
    DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => now()->subHour(),
        'finalizado_em' => null,
    ]);

    $response = $this->getJson(
        '/api/incendios/'.$incendio->id.'/despachos?finalizado=false',
        despachoBrigadaAuthHeaders()
    );

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.finalizado_em'))->toBeNull();
});

test('test_retorna_404_para_incendio_inexistente', function () {
    $id = (string) Str::uuid();

    $this->getJson('/api/incendios/'.$id.'/despachos', despachoBrigadaAuthHeaders())
        ->assertNotFound();
});

test('test_retorna_despacho_do_incendio', function () {
    $incendio = Incendio::factory()->create();
    $despacho = DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => Carbon::parse('2024-06-01 10:00:00'),
        'chegada_em' => Carbon::parse('2024-06-01 10:45:00'),
    ]);

    $response = $this->getJson(
        '/api/incendios/'.$incendio->id.'/despachos/'.$despacho->id,
        despachoBrigadaAuthHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.id', $despacho->id)
        ->assertJsonPath('data.incendio_id', $incendio->id)
        ->assertJsonPath('data.tempo_resposta_minutos', 45);
});

test('test_retorna_404_para_despacho_de_outro_incendio', function () {
    $incendioA = Incendio::factory()->create();
    $incendioB = Incendio::factory()->create();
    $despachoB = DespachoBrigada::factory()->create(['incendio_id' => $incendioB->id]);

    $this->getJson(
        '/api/incendios/'.$incendioA->id.'/despachos/'.$despachoB->id,
        despachoBrigadaAuthHeaders()
    )->assertNotFound();
});

test('test_despacha_brigada_para_incendio', function () {
    $incendio = Incendio::factory()->create();
    $brigada = Brigada::factory()->create();

    $response = $this->postJson(
        '/api/incendios/'.$incendio->id.'/despachos',
        ['brigada_id' => $brigada->id, 'observacoes' => 'Prioridade alta'],
        despachoBrigadaAuthHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('data.incendio_id', $incendio->id)
        ->assertJsonPath('data.brigada_id', $brigada->id)
        ->assertJsonPath('data.observacoes', 'Prioridade alta');

    $this->assertDatabaseHas('despachos_brigada', [
        'incendio_id' => $incendio->id,
        'brigada_id' => $brigada->id,
    ]);
});

test('test_despachado_em_preenchido_automaticamente', function () {
    Carbon::setTestNow(Carbon::parse('2025-03-15 14:30:00'));

    $incendio = Incendio::factory()->create();
    $brigada = Brigada::factory()->create();

    $response = $this->postJson(
        '/api/incendios/'.$incendio->id.'/despachos',
        ['brigada_id' => $brigada->id],
        despachoBrigadaAuthHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('data.despachado_em', '2025-03-15T14:30:00.000000Z');

    Carbon::setTestNow();
});

test('test_marca_brigada_como_indisponivel', function () {
    $incendio = Incendio::factory()->create();
    $brigada = Brigada::factory()->create(['disponivel' => true]);

    $this->postJson(
        '/api/incendios/'.$incendio->id.'/despachos',
        ['brigada_id' => $brigada->id],
        despachoBrigadaAuthHeaders()
    )->assertCreated();

    expect($brigada->fresh()->disponivel)->toBeFalse();
});

test('test_retorna_409_se_brigada_ja_despachada_para_incendio', function () {
    $incendio = Incendio::factory()->create();
    $brigada = Brigada::factory()->create();

    DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'brigada_id' => $brigada->id,
        'finalizado_em' => null,
    ]);

    $this->postJson(
        '/api/incendios/'.$incendio->id.'/despachos',
        ['brigada_id' => $brigada->id],
        despachoBrigadaAuthHeaders()
    )->assertStatus(409)
        ->assertJsonPath('message', 'Esta brigada já possui um despacho em aberto para este incêndio.');
});

test('test_registra_log_de_auditoria', function () {
    $usuario = Usuario::factory()->create();
    $incendio = Incendio::factory()->create();
    $brigada = Brigada::factory()->create();

    $response = $this->postJson(
        '/api/incendios/'.$incendio->id.'/despachos',
        ['brigada_id' => $brigada->id],
        despachoBrigadaAuthHeaders($usuario)
    );

    $despachoId = $response->json('data.id');

    $log = LogAuditoria::query()
        ->where('acao', 'despacho_brigada')
        ->where('entidade_tipo', 'despachos_brigada')
        ->where('entidade_id', $despachoId)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});

test('test_registra_chegada_da_brigada', function () {
    $incendio = Incendio::factory()->create();
    $despacho = DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => Carbon::parse('2024-06-01 08:00:00'),
        'chegada_em' => null,
    ]);

    $response = $this->patchJson(
        '/api/incendios/'.$incendio->id.'/despachos/'.$despacho->id.'/chegada',
        ['chegada_em' => '2024-06-01T09:30:00Z'],
        despachoBrigadaAuthHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.chegada_em', '2024-06-01T09:30:00.000000Z');
});

test('test_retorna_422_se_chegada_ja_registrada', function () {
    $incendio = Incendio::factory()->create();
    $despacho = DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => Carbon::parse('2024-06-01 08:00:00'),
        'chegada_em' => Carbon::parse('2024-06-01 09:00:00'),
    ]);

    $this->patchJson(
        '/api/incendios/'.$incendio->id.'/despachos/'.$despacho->id.'/chegada',
        ['chegada_em' => '2024-06-01T10:00:00Z'],
        despachoBrigadaAuthHeaders()
    )->assertUnprocessable()
        ->assertJsonPath('message', 'Chegada já registrada');
});

test('test_registra_log_de_auditoria_na_chegada', function () {
    $usuario = Usuario::factory()->create();
    $incendio = Incendio::factory()->create();
    $despacho = DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => Carbon::parse('2024-06-01 08:00:00'),
        'chegada_em' => null,
    ]);

    $this->patchJson(
        '/api/incendios/'.$incendio->id.'/despachos/'.$despacho->id.'/chegada',
        ['chegada_em' => '2024-06-01T09:30:00Z'],
        despachoBrigadaAuthHeaders($usuario)
    )->assertOk();

    $log = LogAuditoria::query()
        ->where('acao', 'chegada_brigada')
        ->where('entidade_tipo', 'despachos_brigada')
        ->where('entidade_id', $despacho->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});

test('test_finaliza_despacho', function () {
    $incendio = Incendio::factory()->create();
    $despacho = DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => Carbon::parse('2024-06-01 08:00:00'),
        'chegada_em' => Carbon::parse('2024-06-01 09:00:00'),
        'finalizado_em' => null,
    ]);

    $response = $this->patchJson(
        '/api/incendios/'.$incendio->id.'/despachos/'.$despacho->id.'/finalizar',
        ['finalizado_em' => '2024-06-01T18:00:00Z', 'observacoes' => 'Fogo contido'],
        despachoBrigadaAuthHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.finalizado_em', '2024-06-01T18:00:00.000000Z')
        ->assertJsonPath('data.observacoes', 'Fogo contido');
});

test('test_marca_brigada_como_disponivel_ao_finalizar', function () {
    $incendio = Incendio::factory()->create();
    $brigada = Brigada::factory()->create(['disponivel' => false]);
    $despacho = DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'brigada_id' => $brigada->id,
        'despachado_em' => Carbon::parse('2024-06-01 08:00:00'),
        'chegada_em' => Carbon::parse('2024-06-01 09:00:00'),
        'finalizado_em' => null,
    ]);

    $this->patchJson(
        '/api/incendios/'.$incendio->id.'/despachos/'.$despacho->id.'/finalizar',
        ['finalizado_em' => '2024-06-01T18:00:00Z'],
        despachoBrigadaAuthHeaders()
    )->assertOk();

    expect($brigada->fresh()->disponivel)->toBeTrue();
});

test('test_retorna_422_se_brigada_nao_chegou', function () {
    $incendio = Incendio::factory()->create();
    $despacho = DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => Carbon::parse('2024-06-01 08:00:00'),
        'chegada_em' => null,
        'finalizado_em' => null,
    ]);

    $this->patchJson(
        '/api/incendios/'.$incendio->id.'/despachos/'.$despacho->id.'/finalizar',
        ['finalizado_em' => '2024-06-01T18:00:00Z'],
        despachoBrigadaAuthHeaders()
    )->assertUnprocessable()
        ->assertJsonPath('message', 'Brigada ainda não chegou ao local');
});

test('test_retorna_422_se_despacho_ja_finalizado', function () {
    $incendio = Incendio::factory()->create();
    $despacho = DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => Carbon::parse('2024-06-01 08:00:00'),
        'chegada_em' => Carbon::parse('2024-06-01 09:00:00'),
        'finalizado_em' => Carbon::parse('2024-06-01 12:00:00'),
    ]);

    $this->patchJson(
        '/api/incendios/'.$incendio->id.'/despachos/'.$despacho->id.'/finalizar',
        ['finalizado_em' => '2024-06-01T20:00:00Z'],
        despachoBrigadaAuthHeaders()
    )->assertUnprocessable()
        ->assertJsonPath('message', 'Despacho já finalizado');
});

test('test_registra_log_de_auditoria_na_finalizacao', function () {
    $usuario = Usuario::factory()->create();
    $incendio = Incendio::factory()->create();
    $despacho = DespachoBrigada::factory()->create([
        'incendio_id' => $incendio->id,
        'despachado_em' => Carbon::parse('2024-06-01 08:00:00'),
        'chegada_em' => Carbon::parse('2024-06-01 09:00:00'),
        'finalizado_em' => null,
    ]);

    $this->patchJson(
        '/api/incendios/'.$incendio->id.'/despachos/'.$despacho->id.'/finalizar',
        ['finalizado_em' => '2024-06-01T18:00:00Z'],
        despachoBrigadaAuthHeaders($usuario)
    )->assertOk();

    $log = LogAuditoria::query()
        ->where('acao', 'finalizacao_despacho')
        ->where('entidade_tipo', 'despachos_brigada')
        ->where('entidade_id', $despacho->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});
