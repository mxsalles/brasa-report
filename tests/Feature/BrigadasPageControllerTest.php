<?php

use App\Enums\FuncaoUsuario;
use App\Models\Brigada;
use App\Models\DespachoBrigada;
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

test('despachos recentes aparecem nas props', function () {
    $usuario = Usuario::factory()->verified()->create();
    DespachoBrigada::factory()->count(2)->create();

    $response = $this->actingAs($usuario)
        ->get(route('brigadas'));

    $props = $response->original->getData()['page']['props'];

    expect($props['despachosRecentes'])->toHaveCount(2);
});

test('despachos recentes limitados a 5', function () {
    $usuario = Usuario::factory()->verified()->create();
    DespachoBrigada::factory()->count(8)->create();

    $response = $this->actingAs($usuario)
        ->get(route('brigadas'));

    $props = $response->original->getData()['page']['props'];

    expect($props['despachosRecentes'])->toHaveCount(5);
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
