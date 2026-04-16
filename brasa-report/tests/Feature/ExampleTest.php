<?php

use App\Models\Usuario;

test('guests visiting home are redirected to login', function () {
    $this->get(route('home'))
        ->assertRedirect(route('login'));
});

test('authenticated users visiting home are redirected to dashboard', function () {
    $user = Usuario::factory()->verified()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});
