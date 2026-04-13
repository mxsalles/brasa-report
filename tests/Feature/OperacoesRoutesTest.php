<?php

use App\Models\Usuario;

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
        $response->assertOk();
    }
});
