<?php

use App\Models\Alerta;
use App\Models\Usuario;
use Inertia\Testing\AssertableInertia as Assert;

test('pagina alertas renderiza inertia com lista inicial', function () {
    $usuario = Usuario::factory()->verified()->create();
    Alerta::factory()->count(2)->create();

    $response = $this->actingAs($usuario)->get(route('alertas'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('alertas')
            ->has('alertas.data', 2)
            ->where('alertas.meta.total', 2)
            ->where('alertas.meta.per_page', 20)
        );
});
