<?php

use App\Enums\FuncaoUsuario;
use App\Models\Usuario;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('novos usuarios podem se registrar', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'nome' => 'Test User',
        'email' => 'test@example.com',
        'cpf' => '12345678901',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/email/verify');

    $usuario = Usuario::query()->where('email', 'test@example.com')->first();

    expect($usuario)->not->toBeNull();
    Notification::assertSentTo($usuario, VerifyEmail::class);
});

test('registro requer nome email cpf senha', function () {
    $response = $this->post(route('register.store'), []);

    $response->assertSessionHasErrors(['nome', 'email', 'cpf', 'password']);
    $this->assertGuest();
});

test('cpf duplicado retorna erro', function () {
    $existente = Usuario::factory()->create([
        'cpf' => '52998224725',
    ]);

    $response = $this->from(route('register'))->post(route('register.store'), [
        'nome' => 'Outro Nome',
        'email' => 'outro@example.com',
        'cpf' => $existente->cpf,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('cpf');
    $this->assertGuest();
});

test('email duplicado retorna erro', function () {
    $existente = Usuario::factory()->create([
        'email' => 'existente@example.com',
    ]);

    $response = $this->from(route('register'))->post(route('register.store'), [
        'nome' => 'Outro Nome',
        'email' => $existente->email,
        'cpf' => '98765432109',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('funcao padrao e user', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'nome' => 'Novo Usuário',
        'email' => 'novo@example.com',
        'cpf' => '11122233344',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $usuario = Usuario::query()->where('email', 'novo@example.com')->first();

    expect($usuario)->not->toBeNull();
    expect($usuario->funcao)->toBe(FuncaoUsuario::User);
});

test('email de verificacao enviado apos registro', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'nome' => 'Verify Me',
        'email' => 'verify@example.com',
        'cpf' => '55566677788',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $usuario = Usuario::query()->where('email', 'verify@example.com')->first();

    expect($usuario)->not->toBeNull();
    Notification::assertSentTo($usuario, VerifyEmail::class);
});
