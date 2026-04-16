<?php

use App\Models\Usuario;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = Usuario::factory()->verified()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('dados', fn (Assert $dados) => $dados
            ->has('incendios')
            ->has('alertas')
            ->has('ultimo_registro')
            ->etc()
        )
    );
});
