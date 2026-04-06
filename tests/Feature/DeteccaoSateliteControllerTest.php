<?php

use App\Models\DeteccaoSatelite;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function deteccaoSateliteAuthHeaders(?Usuario $usuario = null): array
{
    $usuario ??= Usuario::factory()->create();

    return [
        'Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken,
    ];
}

test('test_lista_deteccoes_paginadas', function () {
    DeteccaoSatelite::factory()->count(25)->create();

    $response = $this->getJson('/api/deteccoes-satelite', deteccaoSateliteAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 25);
});

test('test_filtra_por_fonte', function () {
    DeteccaoSatelite::factory()->create(['fonte' => 'NASA FIRMS']);
    DeteccaoSatelite::factory()->create(['fonte' => 'Outra']);

    $response = $this->getJson('/api/deteccoes-satelite?fonte=NASA%20FIRMS', deteccaoSateliteAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.fonte'))->toBe('NASA FIRMS');
});

test('test_filtra_por_confianca_minima', function () {
    DeteccaoSatelite::factory()->create(['confianca' => 69]);
    DeteccaoSatelite::factory()->create(['confianca' => 70]);
    DeteccaoSatelite::factory()->create(['confianca' => 90]);

    $response = $this->getJson('/api/deteccoes-satelite?confianca_min=70', deteccaoSateliteAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('test_filtra_por_intervalo_de_data', function () {
    DeteccaoSatelite::factory()->create(['detectado_em' => '2023-12-31 12:00:00+00']);
    DeteccaoSatelite::factory()->create(['detectado_em' => '2024-01-15 12:00:00+00']);
    DeteccaoSatelite::factory()->create(['detectado_em' => '2025-01-01 12:00:00+00']);

    $response = $this->getJson('/api/deteccoes-satelite?de=2024-01-01&ate=2024-12-31', deteccaoSateliteAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.detectado_em'))->not->toBeNull();
});

test('test_index_requer_autenticacao', function () {
    $this->getJson('/api/deteccoes-satelite')->assertUnauthorized();
});

test('test_retorna_deteccao', function () {
    $deteccao = DeteccaoSatelite::factory()->create();

    $response = $this->getJson('/api/deteccoes-satelite/'.$deteccao->id, deteccaoSateliteAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('data.id', $deteccao->id);
});

test('test_retorna_404_para_deteccao_inexistente', function () {
    $id = (string) Str::uuid();

    $this->getJson('/api/deteccoes-satelite/'.$id, deteccaoSateliteAuthHeaders())
        ->assertNotFound();
});

test('test_registra_deteccao_com_dados_validos', function () {
    $payload = [
        'latitude' => -16.5,
        'longitude' => -56.75,
        'detectado_em' => '2024-01-15 12:00:00+00',
        'confianca' => 88.5,
        'fonte' => 'NASA FIRMS',
    ];

    $response = $this->postJson('/api/deteccoes-satelite', $payload, deteccaoSateliteAuthHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.fonte', 'NASA FIRMS')
        ->assertJsonPath('data.confianca', '88.50');

    $this->assertDatabaseHas('deteccoes_satelite', [
        'fonte' => 'NASA FIRMS',
    ]);
});

test('test_aplica_fonte_padrao_quando_nao_informada', function () {
    $payload = [
        'latitude' => -16.5,
        'longitude' => -56.75,
        'detectado_em' => '2024-01-15 12:00:00+00',
        'confianca' => 50,
    ];

    $response = $this->postJson('/api/deteccoes-satelite', $payload, deteccaoSateliteAuthHeaders());

    $response->assertCreated()
        ->assertJsonPath('data.fonte', 'NASA FIRMS');
});

test('test_retorna_422_com_confianca_fora_do_intervalo', function () {
    $this->postJson('/api/deteccoes-satelite', [
        'latitude' => -16.5,
        'longitude' => -56.75,
        'detectado_em' => '2024-01-15 12:00:00+00',
        'confianca' => 101,
    ], deteccaoSateliteAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['confianca']);
});

test('test_retorna_422_com_coordenadas_invalidas', function () {
    $this->postJson('/api/deteccoes-satelite', [
        'latitude' => 200,
        'longitude' => 0,
        'detectado_em' => '2024-01-15 12:00:00+00',
        'confianca' => 50,
    ], deteccaoSateliteAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude']);
});

test('test_registra_log_de_auditoria_na_ingestao', function () {
    $usuario = Usuario::factory()->create();

    $this->postJson('/api/deteccoes-satelite', [
        'latitude' => -16.5,
        'longitude' => -56.75,
        'detectado_em' => '2024-01-15 12:00:00+00',
        'confianca' => 50,
    ], deteccaoSateliteAuthHeaders($usuario))->assertCreated();

    $deteccao = DeteccaoSatelite::query()->latest('detectado_em')->first();

    expect($deteccao)->not->toBeNull();

    $log = LogAuditoria::query()
        ->where('acao', 'ingestao_deteccao_satelite')
        ->where('entidade_tipo', 'deteccoes_satelite')
        ->where('entidade_id', $deteccao->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});

test('test_registra_lote_de_deteccoes', function () {
    $payload = [
        'deteccoes' => [
            [
                'latitude' => -16.5,
                'longitude' => -56.75,
                'detectado_em' => '2024-01-01 12:00:00+00',
                'confianca' => 70,
                'fonte' => 'NASA FIRMS',
            ],
            [
                'latitude' => -16.6,
                'longitude' => -56.76,
                'detectado_em' => '2024-01-02 12:00:00+00',
                'confianca' => 71,
            ],
        ],
    ];

    $this->postJson('/api/deteccoes-satelite/lote', $payload, deteccaoSateliteAuthHeaders())
        ->assertCreated();

    expect(DeteccaoSatelite::query()->count())->toBe(2);
});

test('test_retorna_total_de_registros_inseridos', function () {
    $payload = [
        'deteccoes' => [
            [
                'latitude' => -16.5,
                'longitude' => -56.75,
                'detectado_em' => '2024-01-01 12:00:00+00',
                'confianca' => 70,
            ],
            [
                'latitude' => -16.6,
                'longitude' => -56.76,
                'detectado_em' => '2024-01-02 12:00:00+00',
                'confianca' => 71,
            ],
            [
                'latitude' => -16.7,
                'longitude' => -56.77,
                'detectado_em' => '2024-01-03 12:00:00+00',
                'confianca' => 72,
            ],
        ],
    ];

    $this->postJson('/api/deteccoes-satelite/lote', $payload, deteccaoSateliteAuthHeaders())
        ->assertCreated()
        ->assertJsonPath('total', 3);
});

test('test_lote_falha_atomicamente_se_item_invalido', function () {
    $payload = [
        'deteccoes' => [
            [
                'latitude' => -16.5,
                'longitude' => -56.75,
                'detectado_em' => '2024-01-01 12:00:00+00',
                'confianca' => 70,
            ],
            [
                'latitude' => -16.6,
                'longitude' => -56.76,
                'detectado_em' => '2024-01-02 12:00:00+00',
                'confianca' => 999,
            ],
        ],
    ];

    $this->postJson('/api/deteccoes-satelite/lote', $payload, deteccaoSateliteAuthHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['deteccoes.1.confianca']);

    expect(DeteccaoSatelite::query()->count())->toBe(0);
});

test('test_registra_log_de_auditoria_com_total_do_lote', function () {
    $usuario = Usuario::factory()->create();

    $payload = [
        'deteccoes' => [
            [
                'latitude' => -16.5,
                'longitude' => -56.75,
                'detectado_em' => '2024-01-01 12:00:00+00',
                'confianca' => 70,
            ],
            [
                'latitude' => -16.6,
                'longitude' => -56.76,
                'detectado_em' => '2024-01-02 12:00:00+00',
                'confianca' => 71,
            ],
        ],
    ];

    $this->postJson('/api/deteccoes-satelite/lote', $payload, deteccaoSateliteAuthHeaders($usuario))
        ->assertCreated()
        ->assertJsonPath('total', 2);

    $log = LogAuditoria::query()
        ->where('acao', 'ingestao_lote_deteccoes_satelite')
        ->where('entidade_tipo', 'deteccoes_satelite')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id)
        ->and($log->dados_json['total'])->toBe(2);
});
