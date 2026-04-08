<?php

use App\Enums\TipoAlerta;
use App\Models\Alerta;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Support\Str;

function alertaAuthHeaders(?Usuario $usuario = null): array
{
    $usuario ??= Usuario::factory()->create();

    return [
        'Authorization' => 'Bearer '.$usuario->createToken('test')->plainTextToken,
    ];
}

test('test_lista_alertas_paginados', function () {
    Alerta::factory()->count(25)->create();

    $response = $this->getJson('/api/alertas', alertaAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.total', 25);
});

test('test_filtra_por_entregue', function () {
    Alerta::factory()->create(['entregue' => true]);
    Alerta::factory()->create(['entregue' => false]);

    $response = $this->getJson('/api/alertas?entregue=true', alertaAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.entregue'))->toBeTrue();
});

test('test_filtra_por_tipo', function () {
    Alerta::factory()->create(['tipo' => TipoAlerta::FogoDetectado]);
    Alerta::factory()->create(['tipo' => TipoAlerta::UmidadeBaixa]);

    $response = $this->getJson('/api/alertas?tipo=fogo_detectado', alertaAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.tipo'))->toBe('fogo_detectado');
});

test('test_filtra_por_origem_tabela', function () {
    Alerta::factory()->create(['origem_tabela' => 'incendios']);
    Alerta::factory()->create(['origem_tabela' => 'deteccoes_satelite']);

    $response = $this->getJson('/api/alertas?origem_tabela=incendios', alertaAuthHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.origem_tabela'))->toBe('incendios');
});

test('test_index_requer_autenticacao', function () {
    $this->getJson('/api/alertas')->assertUnauthorized();
});

test('test_retorna_alerta', function () {
    $alerta = Alerta::factory()->create([
        'tipo' => TipoAlerta::TemperaturaAlta,
        'mensagem' => 'Alerta de teste',
        'origem_tabela' => 'incendios',
        'entregue' => false,
    ]);

    $response = $this->getJson('/api/alertas/'.$alerta->id, alertaAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('data.id', $alerta->id)
        ->assertJsonPath('data.tipo', 'temperatura_alta')
        ->assertJsonPath('data.mensagem', 'Alerta de teste')
        ->assertJsonPath('data.origem_id', $alerta->origem_id)
        ->assertJsonPath('data.origem_tabela', 'incendios')
        ->assertJsonPath('data.entregue', false);
});

test('test_retorna_404_para_alerta_inexistente', function () {
    $id = (string) Str::uuid();

    $this->getJson('/api/alertas/'.$id, alertaAuthHeaders())
        ->assertNotFound();
});

test('test_marca_alerta_como_entregue', function () {
    $alerta = Alerta::factory()->create(['entregue' => false]);

    $response = $this->patchJson('/api/alertas/'.$alerta->id.'/entregue', [], alertaAuthHeaders());

    $response->assertOk()
        ->assertJsonPath('data.id', $alerta->id)
        ->assertJsonPath('data.entregue', true);

    $this->assertDatabaseHas('alertas', [
        'id' => $alerta->id,
        'entregue' => true,
    ]);
});

test('test_retorna_422_se_alerta_ja_entregue', function () {
    $alerta = Alerta::factory()->create(['entregue' => true]);

    $this->patchJson('/api/alertas/'.$alerta->id.'/entregue', [], alertaAuthHeaders())
        ->assertUnprocessable()
        ->assertJson([
            'message' => 'Alerta já marcado como entregue',
        ]);
});

test('test_registra_log_de_auditoria', function () {
    $usuario = Usuario::factory()->create();
    $alerta = Alerta::factory()->create(['entregue' => false]);

    $this->patchJson('/api/alertas/'.$alerta->id.'/entregue', [], alertaAuthHeaders($usuario))
        ->assertOk();

    $log = LogAuditoria::query()
        ->where('acao', 'alerta_marcado_entregue')
        ->where('entidade_tipo', 'alertas')
        ->where('entidade_id', $alerta->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->usuario_id)->toBe($usuario->id);
});
