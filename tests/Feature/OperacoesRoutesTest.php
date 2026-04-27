<?php

use App\Models\Usuario;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login when visiting operacao routes', function () {
    foreach (
        [
            'mapa',
            'registrar-incendio',
            'alertas',
            'brigadas',
            'administracao',
        ] as $name
    ) {
        $response = $this->get(route($name));
        $response->assertRedirect(route('login'));
    }
});

test('authenticated users can visit operacao routes', function () {
    $user = Usuario::factory()->verified()->create();
    $this->actingAs($user);

    foreach (['mapa', 'registrar-incendio', 'alertas', 'brigadas'] as $name) {
        $response = $this->get(route($name));
        $response->assertOk();
    }
});

test('administracao is forbidden for usuario comum', function () {
    $user = Usuario::factory()->verified()->create();
    $this->actingAs($user);

    $this->get(route('administracao'))->assertForbidden();
});

test('gestor pode visitar administracao', function () {
    $user = Usuario::factory()->verified()->gestor()->create();
    $this->actingAs($user);

    $this->get(route('administracao'))->assertOk();
});

test('registrar incendio renders component without area props', function () {
    $user = Usuario::factory()->verified()->create();
    $this->actingAs($user);

    $response = $this->get(route('registrar-incendio'));
    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('registrar-incendio')
    );
});
