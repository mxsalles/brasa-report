<?php

use App\Enums\FuncaoUsuario;
use App\Enums\StatusIncendio;
use App\Models\Brigada;
use App\Models\DespachoBrigada;
use App\Models\Incendio;
use App\Models\Usuario;

test('usuario autenticado acessa pagina de brigadas', function () {
    $usuario = Usuario::factory()->verified()->create(['funcao' => FuncaoUsuario::User]);

    $this->actingAs($usuario)
        ->get(route('brigadas'))
        ->assertOk();
});

test('brigadista acessa pagina de brigadas', function () {
    $usuario = Usuario::factory()->verified()->brigadista()->create();

    $this->actingAs($usuario)
        ->get(route('brigadas'))
        ->assertOk();
});

test('gestor acessa pagina de brigadas', function () {
    $usuario = Usuario::factory()->verified()->gestor()->create();

    $this->actingAs($usuario)
        ->get(route('brigadas'))
        ->assertOk();
});

test('administrador acessa pagina de brigadas', function () {
    $usuario = Usuario::factory()->verified()->administrador()->create();

    $this->actingAs($usuario)
        ->get(route('brigadas'))
        ->assertOk();
});

test('visitante nao autenticado e redirecionado para login', function () {
    $this->get(route('brigadas'))
        ->assertRedirect(route('login'));
});

test('podeGerenciar e true para gestor', function () {
    $usuario = Usuario::factory()->verified()->gestor()->create();

    $response = $this->actingAs($usuario)
        ->get(route('brigadas'));

    $response->assertOk();
    $props = $response->original->getData()['page']['props'];

    expect($props['podeGerenciar'])->toBeTrue()
        ->and($props['funcaoAutenticado'])->toBe('gestor');
});

test('podeGerenciar e true para administrador', function () {
    $usuario = Usuario::factory()->verified()->administrador()->create();

    $response = $this->actingAs($usuario)
        ->get(route('brigadas'));

    $response->assertOk();
    $props = $response->original->getData()['page']['props'];

    expect($props['podeGerenciar'])->toBeTrue()
        ->and($props['funcaoAutenticado'])->toBe('administrador');
});

test('podeGerenciar e false para user', function () {
    $usuario = Usuario::factory()->verified()->create(['funcao' => FuncaoUsuario::User]);

    $response = $this->actingAs($usuario)
        ->get(route('brigadas'));

    $response->assertOk();
    $props = $response->original->getData()['page']['props'];

    expect($props['podeGerenciar'])->toBeFalse()
        ->and($props['funcaoAutenticado'])->toBe('user');
});

test('podeGerenciar e false para brigadista', function () {
    $usuario = Usuario::factory()->verified()->brigadista()->create();

    $response = $this->actingAs($usuario)
        ->get(route('brigadas'));

    $response->assertOk();
    $props = $response->original->getData()['page']['props'];

    expect($props['podeGerenciar'])->toBeFalse()
        ->and($props['funcaoAutenticado'])->toBe('brigadista');
});

test('brigadas aparecem nas props', function () {
    $usuario = Usuario::factory()->verified()->create();
    Brigada::factory()->count(3)->create();

    $response = $this->actingAs($usuario)
        ->get(route('brigadas'));

    $response->assertOk();
    $props = $response->original->getData()['page']['props'];

    expect($props['brigadas'])->toHaveCount(3);
});

test('brigadas incluem usuarios_count', function () {
    $usuario = Usuario::factory()->verified()->create();
    $brigada = Brigada::factory()->create();
    Usuario::factory()->count(2)->create(['brigada_id' => $brigada->id]);

    $response = $this->actingAs($usuario)
        ->get(route('brigadas'));

    $props = $response->original->getData()['page']['props'];
    $item = collect($props['brigadas'])->firstWhere('id', $brigada->id);

    expect($item)->not->toBeNull()
        ->and($item['usuarios_count'])->toBe(2);
});

test('brigada sem despacho aberto tem operacao_incendio nula', function () {
    $usuario = Usuario::factory()->verified()->create();
    $brigada = Brigada::factory()->create();

    $response = $this->actingAs($usuario)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];
    $item = collect($props['brigadas'])->firstWhere('id', $brigada->id);

    expect($item['operacao_incendio'])->toBeNull();
});

test('brigada com despacho aberto sem chegada inclui operacao em_deslocamento', function () {
    $usuario = Usuario::factory()->verified()->create();
    $brigada = Brigada::factory()->create(['disponivel' => false]);
    $incendio = Incendio::factory()->create(['status' => StatusIncendio::Ativo]);
    DespachoBrigada::factory()->create([
        'brigada_id' => $brigada->id,
        'incendio_id' => $incendio->id,
        'chegada_em' => null,
        'finalizado_em' => null,
    ]);

    $response = $this->actingAs($usuario)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];
    $item = collect($props['brigadas'])->firstWhere('id', $brigada->id);

    expect($item['operacao_incendio'])->not->toBeNull()
        ->and($item['operacao_incendio']['fase'])->toBe('em_deslocamento')
        ->and($item['operacao_incendio']['incendio_status'])->toBe('ativo');
});

test('brigada com despacho aberto e chegada inclui operacao em_combate', function () {
    $usuario = Usuario::factory()->verified()->create();
    $brigada = Brigada::factory()->create(['disponivel' => false]);
    $incendio = Incendio::factory()->create(['status' => StatusIncendio::EmCombate]);
    DespachoBrigada::factory()->create([
        'brigada_id' => $brigada->id,
        'incendio_id' => $incendio->id,
        'chegada_em' => now(),
        'finalizado_em' => null,
    ]);

    $response = $this->actingAs($usuario)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];
    $item = collect($props['brigadas'])->firstWhere('id', $brigada->id);

    expect($item['operacao_incendio'])->not->toBeNull()
        ->and($item['operacao_incendio']['fase'])->toBe('em_combate')
        ->and($item['operacao_incendio']['incendio_status'])->toBe('em_combate');
});

test('despachos ativos aparecem nas props', function () {
    $usuario = Usuario::factory()->verified()->create();
    DespachoBrigada::factory()->count(2)->create(['finalizado_em' => null]);

    $response = $this->actingAs($usuario)
        ->get(route('brigadas'));

    $props = $response->original->getData()['page']['props'];

    expect($props['despachosAtivos'])->toHaveCount(2)
        ->and($props['despachosFinalizados'])->toBeEmpty();
});

test('despachos finalizados aparecem nas props com limite de 20', function () {
    $usuario = Usuario::factory()->verified()->create();
    DespachoBrigada::factory()->count(22)->create(['finalizado_em' => now()]);

    $response = $this->actingAs($usuario)
        ->get(route('brigadas'));

    $props = $response->original->getData()['page']['props'];

    expect($props['despachosFinalizados'])->toHaveCount(20)
        ->and($props['despachosAtivos'])->toBeEmpty();
});

test('despachos separados corretamente entre ativos e finalizados', function () {
    $usuario = Usuario::factory()->verified()->create();
    DespachoBrigada::factory()->count(3)->create(['finalizado_em' => null]);
    DespachoBrigada::factory()->count(2)->create(['finalizado_em' => now()]);

    $response = $this->actingAs($usuario)
        ->get(route('brigadas'));

    $props = $response->original->getData()['page']['props'];

    expect($props['despachosAtivos'])->toHaveCount(3)
        ->and($props['despachosFinalizados'])->toHaveCount(2);
});

test('usuario bloqueado nao acessa pagina de brigadas', function () {
    $usuario = Usuario::factory()->verified()->bloqueado()->create();

    $this->actingAs($usuario)
        ->get(route('brigadas'))
        ->assertForbidden();
});

test('gestor recebe usuarios disponiveis nas props', function () {
    $gestor = Usuario::factory()->verified()->gestor()->create();
    $semBrigada = Usuario::factory()->create(['brigada_id' => null, 'bloqueado' => false]);
    $comBrigada = Usuario::factory()->create(['brigada_id' => Brigada::factory()->create()->id]);
    $bloqueado = Usuario::factory()->bloqueado()->create(['brigada_id' => null]);

    $response = $this->actingAs($gestor)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];
    $ids = collect($props['usuariosDisponiveis'])->pluck('id')->all();

    expect($ids)->toContain($semBrigada->id)
        ->and($ids)->not->toContain($comBrigada->id)
        ->and($ids)->not->toContain($bloqueado->id);
});

test('user nao recebe usuarios disponiveis', function () {
    $user = Usuario::factory()->verified()->create(['funcao' => FuncaoUsuario::User]);

    $response = $this->actingAs($user)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];

    expect($props['usuariosDisponiveis'])->toBeEmpty();
});

test('gestor recebe incendios ativos e contidos nas props', function () {
    $gestor = Usuario::factory()->verified()->gestor()->create();
    Incendio::factory()->create(['status' => StatusIncendio::Ativo]);
    Incendio::factory()->create(['status' => StatusIncendio::EmCombate]);
    Incendio::factory()->create(['status' => StatusIncendio::Contido]);
    Incendio::factory()->create(['status' => StatusIncendio::Resolvido]);

    $response = $this->actingAs($gestor)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];

    expect($props['incendiosAtivos'])->toHaveCount(3);

    $statuses = collect($props['incendiosAtivos'])->pluck('status')->all();
    expect($statuses)->each->toBeIn(['ativo', 'em_combate', 'contido']);
});

test('administrador recebe incendios ativos nas props', function () {
    $admin = Usuario::factory()->verified()->administrador()->create();
    Incendio::factory()->create(['status' => StatusIncendio::Ativo]);

    $response = $this->actingAs($admin)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];

    expect($props['incendiosAtivos'])->toHaveCount(1)
        ->and($props['incendiosAtivos'][0]['status'])->toBe('ativo');
});

test('user nao recebe incendios ativos', function () {
    $user = Usuario::factory()->verified()->create(['funcao' => FuncaoUsuario::User]);
    Incendio::factory()->create(['status' => StatusIncendio::Ativo]);

    $response = $this->actingAs($user)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];

    expect($props['incendiosAtivos'])->toBeEmpty();
});

test('brigadista nao recebe incendios ativos', function () {
    $brigadista = Usuario::factory()->verified()->brigadista()->create();
    Incendio::factory()->create(['status' => StatusIncendio::Ativo]);

    $response = $this->actingAs($brigadista)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];

    expect($props['incendiosAtivos'])->toBeEmpty();
});

test('incendios ativos incluem area_nome e coordenadas', function () {
    $gestor = Usuario::factory()->verified()->gestor()->create();
    $incendio = Incendio::factory()->create(['status' => StatusIncendio::Ativo]);

    $response = $this->actingAs($gestor)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];

    $item = $props['incendiosAtivos'][0];

    expect($item)->toHaveKeys(['id', 'latitude', 'longitude', 'detectado_em', 'nivel_risco', 'status', 'area_nome'])
        ->and($item['id'])->toBe($incendio->id)
        ->and($item['latitude'])->not->toBeNull()
        ->and($item['longitude'])->not->toBeNull()
        ->and($item['area_nome'])->not->toBeNull();
});

test('incendios resolvidos nao aparecem em incendiosAtivos', function () {
    $gestor = Usuario::factory()->verified()->gestor()->create();
    $resolvido = Incendio::factory()->create(['status' => StatusIncendio::Resolvido]);

    $response = $this->actingAs($gestor)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];

    $ids = collect($props['incendiosAtivos'])->pluck('id')->all();
    expect($ids)->not->toContain($resolvido->id);
});

test('despachos incluem incendio_id e observacoes', function () {
    $usuario = Usuario::factory()->verified()->create();
    $despacho = DespachoBrigada::factory()->create([
        'observacoes' => 'Prioridade máxima',
        'finalizado_em' => null,
    ]);

    $response = $this->actingAs($usuario)->get(route('brigadas'));
    $props = $response->original->getData()['page']['props'];

    $item = collect($props['despachosAtivos'])->firstWhere('id', $despacho->id);

    expect($item)->not->toBeNull()
        ->and($item)->toHaveKeys(['incendio_id', 'observacoes'])
        ->and($item['incendio_id'])->toBe($despacho->incendio_id)
        ->and($item['observacoes'])->toBe('Prioridade máxima');
});
