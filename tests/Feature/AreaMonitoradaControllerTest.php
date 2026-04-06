<?php

use App\Enums\NivelRiscoIncendio;
use App\Enums\StatusIncendio;
use App\Models\AreaMonitorada;
use App\Models\Incendio;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use App\Services\GeoPackageService;
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
            'geometria_wkt' => null,
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
        'geometria_wkt' => null,
        'importado_em' => now(),
    ]);
    AreaMonitorada::query()->create([
        'nome' => 'Cerrado Leste',
        'caminho_geopackage' => null,
        'geometria_wkt' => null,
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
        'caminho_geopackage' => 'geopackages/x.gpkg',
        'geometria_wkt' => 'RAW',
        'importado_em' => now(),
    ]);

    $response = $this->getJson('/api/areas-monitoradas/'.$area->id, areaMonitoradaAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('data.id', $area->id)
        ->assertJsonPath('data.nome', 'Área Teste')
        ->assertJsonPath('data.geometria_wkt', 'RAW');
});

test('test_retorna_404_para_area_inexistente', function () {
    $id = (string) Str::uuid();

    $this->getJson('/api/areas-monitoradas/'.$id, areaMonitoradaAuthHeaders())
        ->assertNotFound();
});

test('test_cria_area_com_geopackage_valido', function () {
    $this->mock(GeoPackageService::class, function ($mock): void {
        $mock->shouldReceive('extrairWkt')
            ->once()
            ->andReturn([
                'wkt' => 'GEOM_BRUTA',
                'caminho' => 'geopackages/abc.gpkg',
            ]);
    });

    $file = UploadedFile::fake()->create('area.gpkg', 100);

    $response = $this->withHeaders(areaMonitoradaAuthHeaders())
        ->post('/api/areas-monitoradas', [
            'nome' => 'Nova Área Monitorada',
            'geopackage' => $file,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.nome', 'Nova Área Monitorada')
        ->assertJsonPath('data.geometria_wkt', 'GEOM_BRUTA')
        ->assertJsonPath('data.caminho_geopackage', 'geopackages/abc.gpkg');

    $this->assertDatabaseHas('areas_monitoradas', [
        'nome' => 'Nova Área Monitorada',
        'caminho_geopackage' => 'geopackages/abc.gpkg',
        'geometria_wkt' => 'GEOM_BRUTA',
    ]);
});

test('test_retorna_422_sem_arquivo_geopackage', function () {
    $this->withHeaders(areaMonitoradaAuthHeaders())
        ->post('/api/areas-monitoradas', [
            'nome' => 'Sem arquivo',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['geopackage']);
});

test('test_retorna_422_com_arquivo_de_tipo_invalido', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 100);

    $this->withHeaders(areaMonitoradaAuthHeaders())
        ->post('/api/areas-monitoradas', [
            'nome' => 'Com PDF',
            'geopackage' => $file,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['geopackage']);
});

test('test_registra_log_de_auditoria_na_criacao', function () {
    $this->mock(GeoPackageService::class, function ($mock): void {
        $mock->shouldReceive('extrairWkt')
            ->once()
            ->andReturn([
                'wkt' => 'X',
                'caminho' => 'geopackages/log.gpkg',
            ]);
    });

    $usuario = Usuario::factory()->create();
    $file = UploadedFile::fake()->create('area.gpkg', 100);

    $this->withHeaders(areaMonitoradaAuthHeaders($usuario))
        ->post('/api/areas-monitoradas', [
            'nome' => 'Área Log',
            'geopackage' => $file,
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
        'geometria_wkt' => null,
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
    $path = 'geopackages/para-remover.gpkg';
    Storage::disk('local')->put($path, 'conteudo');

    $area = AreaMonitorada::query()->create([
        'nome' => 'Remover',
        'caminho_geopackage' => $path,
        'geometria_wkt' => 'wkt',
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
        'geometria_wkt' => null,
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
        'geometria_wkt' => null,
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
