<?php

use App\Models\Usuario;

test('profile page is displayed', function () {
    $user = Usuario::factory()->verified()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = Usuario::factory()->verified()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'nome' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->nome)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});

test('profile nome can be updated when email is unchanged', function () {
    $user = Usuario::factory()->verified()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'nome' => 'Novo Nome',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->nome)->toBe('Novo Nome');
});

test('user can delete their account', function () {
    $user = Usuario::factory()->verified()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = Usuario::factory()->verified()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
