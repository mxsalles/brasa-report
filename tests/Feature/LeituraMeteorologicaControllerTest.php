<?php

use App\Models\Incendio;
use App\Models\LeituraMeteorologica;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function leituraMeteorologicaAuthHeaders(?Usuario $usuario = null): array
{
    $usuario ??= Usuario::factory()->create();

    return [
        'Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken,
        'Accept' => 'application/json',
    ];
}

function payloadLeituraValido(): array
{
    return [
        'temperatura' => 25.5,
        'umidade' => 55,
        'velocidade_vento' => 12.5,
        'registrado_em' => '2024-06-01T12:00:00Z',
    ];
}

test('test_lista_leituras_do_incendio', function () {
    $incendio = Incendio::factory()->create();
    LeituraMeteorologica::factory()->count(3)->create([
        'incendio_id' => $incendio->id,
        'registrado_em' => now()->subDay(),
    ]);

    $response = $this->getJson('/api/incendios/'.$incendio->id.'/leituras', leituraMeteorologicaAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 3);
    expect($response->json('data'))->toHaveCount(3);
});

test('test_filtra_leituras_que_geram_alerta', function () {
    $incendio = Incendio::factory()->create();
    LeituraMeteorologica::factory()->create([
        'incendio_id' => $incendio->id,
        'gera_alerta' => true,
        'registrado_em' => now()->subHours(2),
    ]);
    LeituraMeteorologica::factory()->create([
        'incendio_id' => $incendio->id,
        'gera_alerta' => false,
        'registrado_em' => now()->subHour(),
    ]);

    $response = $this->getJson(
        '/api/incendios/'.$incendio->id.'/leituras?gera_alerta=true',
        leituraMeteorologicaAuthHeaders()
    );

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.gera_alerta'))->toBeTrue();
});

test('test_retorna_404_para_incendio_inexistente', function () {
    $id = (string) Str::uuid();
    $headers = leituraMeteorologicaAuthHeaders();

    $this->getJson('/api/incendios/'.$id.'/leituras', $headers)->assertNotFound();

    $this->postJson('/api/incendios/'.$id.'/leituras', payloadLeituraValido(), $headers)
        ->assertNotFound();
});

test('test_index_requer_autenticacao', function () {
    $incendio = Incendio::factory()->create();

    $this->getJson('/api/incendios/'.$incendio->id.'/leituras')->assertUnauthorized();
});

test('test_retorna_leitura_do_incendio', function () {
    $incendio = Incendio::factory()->create();
    $leitura = LeituraMeteorologica::factory()->create(['incendio_id' => $incendio->id]);

    $response = $this->getJson(
        '/api/incendios/'.$incendio->id.'/leituras/'.$leitura->id,
        leituraMeteorologicaAuthHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.id', $leitura->id)
        ->assertJsonPath('data.incendio_id', $incendio->id);
});

test('test_retorna_404_para_leitura_de_outro_incendio', function () {
    $incendioA = Incendio::factory()->create();
    $incendioB = Incendio::factory()->create();
    $leituraB = LeituraMeteorologica::factory()->create(['incendio_id' => $incendioB->id]);

    $this->getJson(
        '/api/incendios/'.$incendioA->id.'/leituras/'.$leituraB->id,
        leituraMeteorologicaAuthHeaders()
    )->assertNotFound();
});

test('test_retorna_404_para_leitura_inexistente', function () {
    $incendio = Incendio::factory()->create();
    $id = (string) Str::uuid();

    $this->getJson(
        '/api/incendios/'.$incendio->id.'/leituras/'.$id,
        leituraMeteorologicaAuthHeaders()
    )->assertNotFound();
});

test('test_registra_leitura_para_incendio', function () {
    $incendio = Incendio::factory()->create();
    $usuario = Usuario::factory()->create();

    $response = $this->postJson(
        '/api/incendios/'.$incendio->id.'/leituras',
        payloadLeituraValido(),
        leituraMeteorologicaAuthHeaders($usuario)
    );

    $response->assertCreated()
        ->assertJsonPath('data.incendio_id', $incendio->id)
        ->assertJsonPath('data.temperatura', '25.50');

    $this->assertDatabaseHas('leituras_meteorologicas', [
        'incendio_id' => $incendio->id,
    ]);
});

test('test_incendio_id_preenchido_pela_rota', function () {
    $incendioA = Incendio::factory()->create();
    $incendioB = Incendio::factory()->create();

    $this->postJson(
        '/api/incendios/'.$incendioA->id.'/leituras',
        [...payloadLeituraValido(), 'incendio_id' => $incendioB->id],
        leituraMeteorologicaAuthHeaders()
    )->assertUnprocessable()
        ->assertJsonValidationErrors(['incendio_id']);

    $response = $this->postJson(
        '/api/incendios/'.$incendioA->id.'/leituras',
        payloadLeituraValido(),
        leituraMeteorologicaAuthHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('data.incendio_id', $incendioA->id);
});

test('test_gera_alerta_quando_temperatura_acima_de_30', function () {
    $incendio = Incendio::factory()->create();

    $response = $this->postJson(
        '/api/incendios/'.$incendio->id.'/leituras',
        [
            'temperatura' => 30.1,
            'umidade' => 60,
            'velocidade_vento' => 5,
            'registrado_em' => '2024-06-01T12:00:00Z',
        ],
        leituraMeteorologicaAuthHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('data.gera_alerta', true);
});

test('test_gera_alerta_quando_umidade_abaixo_de_40', function () {
    $incendio = Incendio::factory()->create();

    $response = $this->postJson(
        '/api/incendios/'.$incendio->id.'/leituras',
        [
            'temperatura' => 25,
            'umidade' => 39.9,
            'velocidade_vento' => 5,
            'registrado_em' => '2024-06-01T12:00:00Z',
        ],
        leituraMeteorologicaAuthHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('data.gera_alerta', true);
});

test('test_nao_gera_alerta_em_condicoes_normais', function () {
    $incendio = Incendio::factory()->create();

    $response = $this->postJson(
        '/api/incendios/'.$incendio->id.'/leituras',
        [
            'temperatura' => 30,
            'umidade' => 40,
            'velocidade_vento' => 5,
            'registrado_em' => '2024-06-01T12:00:00Z',
        ],
        leituraMeteorologicaAuthHeaders()
    );

    $response->assertCreated()
        ->assertJsonPath('data.gera_alerta', false);
});

test('test_registra_log_de_auditoria', function () {
    $usuario = Usuario::factory()->create();
    $incendio = Incendio::factory()->create();

    $response = $this->postJson(
        '/api/incendios/'.$incendio->id.'/leituras',
        payloadLeituraValido(),
        leituraMeteorologicaAuthHeaders($usuario)
    );

    $leituraId = $response->json('data.id');

    $log = LogAuditoria::query()
        ->where('acao', 'registro_leitura_meteorologica')
        ->where('entidade_tipo', 'leituras_meteorologicas')
        ->where('entidade_id', $leituraId)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});
