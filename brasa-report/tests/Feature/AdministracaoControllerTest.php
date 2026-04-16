<?php

use App\Models\Usuario;
use Inertia\Testing\AssertableInertia as Assert;

test('administrador acessa pagina administracao com props', function () {
    $user = Usuario::factory()->verified()->administrador()->create();
    $this->actingAs($user);

    $response = $this->get(route('administracao'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('administracao')
        ->has('usuarios')
        ->has('logsAuditoria')
        ->where('podeGerenciarAdministradores', true)
        ->where('funcaoAutenticado', 'administrador')
    );
});

test('gestor acessa pagina administracao sem gestao de administradores', function () {
    $user = Usuario::factory()->verified()->gestor()->create();
    $this->actingAs($user);

    $response = $this->get(route('administracao'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('administracao')
        ->where('podeGerenciarAdministradores', false)
        ->where('funcaoAutenticado', 'gestor')
    );
});

test('brigadista recebe 403 em administracao', function () {
    $user = Usuario::factory()->verified()->brigadista()->create();
    $this->actingAs($user);

    $this->get(route('administracao'))->assertForbidden();
});
