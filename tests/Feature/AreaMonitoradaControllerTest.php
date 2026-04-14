<?php

use App\Enums\NivelRiscoIncendio;
use App\Enums\StatusIncendio;
use App\Models\AreaMonitorada;
use App\Models\Incendio;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use App\Services\GeoConverterService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function areaMonitoradaAuthHeaders(?Usuario $usuario = null): array
{
    $usuario ??= Usuario::factory()->create();

    return [
        'Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken,
        'Accept' => 'application/json',
    ];
}

test('test_lista_areas_monitoradas_paginadas', function () {
    for ($i = 0; $i < 25; $i++) {
        AreaMonitorada::query()->create([
            'nome' => 'Área '.$i,
            'caminho_geopackage' => null,
            'geometria_geojson' => null,
            'importado_em' => now(),
        ]);
    }

    $response = $this->getJson('/api/areas-monitoradas', areaMonitoradaAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 25);
});

test('test_filtra_areas_por_nome', function () {
    AreaMonitorada::query()->create([
        'nome' => 'Pantanal Norte',
        'caminho_geopackage' => null,
        'geometria_geojson' => null,
        'importado_em' => now(),
    ]);
    AreaMonitorada::query()->create([
        'nome' => 'Cerrado Leste',
        'caminho_geopackage' => null,
        'geometria_geojson' => null,
        'importado_em' => now(),
    ]);

    $response = $this->getJson('/api/areas-monitoradas?nome=pantanal', areaMonitoradaAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.nome'))->toBe('Pantanal Norte');
});

test('test_index_requer_autenticacao', function () {
    $this->getJson('/api/areas-monitoradas')->assertUnauthorized();
});

test('test_retorna_area_monitorada', function () {
    $area = AreaMonitorada::query()->create([
        'nome' => 'Área Teste',
        'caminho_geopackage' => 'geoarquivos/x.zip',
        'geometria_geojson' => ['type' => 'Point', 'coordinates' => [0, 0]],
        'importado_em' => now(),
    ]);

    $response = $this->getJson('/api/areas-monitoradas/'.$area->id, areaMonitoradaAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('data.id', $area->id)
        ->assertJsonPath('data.nome', 'Área Teste')
        ->assertJsonPath('data.geometria_geojson.type', 'Point');
});

test('test_retorna_404_para_area_inexistente', function () {
    $id = (string) Str::uuid();

    $this->getJson('/api/areas-monitoradas/'.$id, areaMonitoradaAuthHeaders())
        ->assertNotFound();
});

test('test_cria_area_com_arquivo_geojson', function () {
    $this->mock(GeoConverterService::class, function ($mock): void {
        $mock->shouldReceive('toGeoJson')
            ->once()
            ->andReturn([
                'type' => 'FeatureCollection',
                'features' => [],
            ]);
    });

    $file = UploadedFile::fake()->create('area.geojson', 100);

    $response = $this->withHeaders(areaMonitoradaAuthHeaders())
        ->post('/api/areas-monitoradas', [
            'nome' => 'Nova Área Monitorada',
            'arquivo' => $file,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.nome', 'Nova Área Monitorada')
        ->assertJsonPath('data.geometria_geojson.type', 'FeatureCollection');

    $this->assertDatabaseHas('areas_monitoradas', [
        'nome' => 'Nova Área Monitorada',
    ]);
});

test('test_cria_area_sem_arquivo', function () {
    $response = $this->withHeaders(areaMonitoradaAuthHeaders())
        ->post('/api/areas-monitoradas', [
            'nome' => 'Área sem geometria',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.nome', 'Área sem geometria')
        ->assertJsonPath('data.geometria_geojson', null);

    $this->assertDatabaseHas('areas_monitoradas', [
        'nome' => 'Área sem geometria',
    ]);
});

test('test_retorna_422_com_arquivo_de_tipo_invalido', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 100);

    $this->withHeaders(areaMonitoradaAuthHeaders())
        ->post('/api/areas-monitoradas', [
            'nome' => 'Com PDF',
            'arquivo' => $file,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['arquivo']);
});

test('test_retorna_422_quando_conversao_falha', function () {
    $this->mock(GeoConverterService::class, function ($mock): void {
        $mock->shouldReceive('toGeoJson')
            ->once()
            ->andThrow(new RuntimeException('Arquivo inválido'));
    });

    $file = UploadedFile::fake()->create('area.geojson', 100);

    $this->withHeaders(areaMonitoradaAuthHeaders())
        ->post('/api/areas-monitoradas', [
            'nome' => 'Falha',
            'arquivo' => $file,
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Arquivo inválido');
});

test('test_registra_log_de_auditoria_na_criacao', function () {
    $this->mock(GeoConverterService::class, function ($mock): void {
        $mock->shouldReceive('toGeoJson')
            ->once()
            ->andReturn(['type' => 'Point', 'coordinates' => [1, 2]]);
    });

    $usuario = Usuario::factory()->create();
    $file = UploadedFile::fake()->create('area.geojson', 100);

    $this->withHeaders(areaMonitoradaAuthHeaders($usuario))
        ->post('/api/areas-monitoradas', [
            'nome' => 'Área Log',
            'arquivo' => $file,
        ])
        ->assertCreated();

    $area = AreaMonitorada::query()->where('nome', 'Área Log')->first();

    expect($area)->not->toBeNull();

    $log = LogAuditoria::query()
        ->where('acao', 'criacao_area_monitorada')
        ->where('entidade_tipo', 'areas_monitoradas')
        ->where('entidade_id', $area->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});

test('test_atualiza_nome_da_area', function () {
    $area = AreaMonitorada::query()->create([
        'nome' => 'Nome Antigo',
        'caminho_geopackage' => null,
        'geometria_geojson' => null,
        'importado_em' => now(),
    ]);

    $this->putJson('/api/areas-monitoradas/'.$area->id, [
        'nome' => 'Nome Novo',
    ], areaMonitoradaAuthHeaders())->assertOk()
        ->assertJsonPath('data.nome', 'Nome Novo');

    expect($area->fresh()->nome)->toBe('Nome Novo');
});

test('test_update_retorna_404_para_area_inexistente', function () {
    $id = (string) Str::uuid();

    $this->putJson('/api/areas-monitoradas/'.$id, [
        'nome' => 'X',
    ], areaMonitoradaAuthHeaders())->assertNotFound();
});

test('test_remove_area_sem_incendios', function () {
    Storage::fake('local');
    $path = 'geoarquivos/para-remover.zip';
    Storage::disk('local')->put($path, 'conteudo');

    $area = AreaMonitorada::query()->create([
        'nome' => 'Remover',
        'caminho_geopackage' => $path,
        'geometria_geojson' => ['type' => 'Point', 'coordinates' => [0, 0]],
        'importado_em' => now(),
    ]);

    $this->deleteJson('/api/areas-monitoradas/'.$area->id, [], areaMonitoradaAuthHeaders())
        ->assertNoContent();

    $this->assertDatabaseMissing('areas_monitoradas', ['id' => $area->id]);
    Storage::disk('local')->assertMissing($path);
});

test('test_retorna_409_ao_remover_area_com_incendios', function () {
    $usuario = Usuario::factory()->create();
    $area = AreaMonitorada::query()->create([
        'nome' => 'Com incêndio',
        'caminho_geopackage' => null,
        'geometria_geojson' => null,
        'importado_em' => now(),
    ]);

    Incendio::query()->create([
        'latitude' => -16.5,
        'longitude' => -56.75,
        'usuario_id' => $usuario->id,
        'area_id' => $area->id,
        'nivel_risco' => NivelRiscoIncendio::Alto,
        'status' => StatusIncendio::Ativo,
    ]);

    $this->deleteJson('/api/areas-monitoradas/'.$area->id, [], areaMonitoradaAuthHeaders())
        ->assertStatus(409)
        ->assertJsonStructure(['message']);

    $this->assertDatabaseHas('areas_monitoradas', ['id' => $area->id]);
});

test('test_registra_log_de_auditoria_na_remocao', function () {
    $usuario = Usuario::factory()->create();
    $area = AreaMonitorada::query()->create([
        'nome' => 'Log remoção',
        'caminho_geopackage' => null,
        'geometria_geojson' => null,
        'importado_em' => now(),
    ]);

    $this->deleteJson('/api/areas-monitoradas/'.$area->id, [], areaMonitoradaAuthHeaders($usuario))
        ->assertNoContent();

    $log = LogAuditoria::query()
        ->where('acao', 'remocao_area_monitorada')
        ->where('entidade_tipo', 'areas_monitoradas')
        ->where('entidade_id', $area->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});
