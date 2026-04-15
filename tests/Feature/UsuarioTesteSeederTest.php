<?php

use App\Models\Usuario;
use Database\Seeders\UsuarioTesteSeeder;
use Illuminate\Support\Facades\Hash;

test('usuario teste seeder is idempotent and creates expected user', function () {
    $seeder = new UsuarioTesteSeeder;
    $seeder->run();
    $seeder->run();

    $user = Usuario::query()->where('email', UsuarioTesteSeeder::EMAIL_TESTE)->first();

    expect($user)->not->toBeNull()
        ->and($user->nome)->toBe('Teste')
        ->and($user->cpf)->toBe(UsuarioTesteSeeder::CPF_TESTE)
        ->and(Hash::check('12345678', $user->senha_hash))->toBeTrue();

    expect(Usuario::query()->where('email', UsuarioTesteSeeder::EMAIL_TESTE)->count())->toBe(1);
});
