<?php

use App\Models\Usuario;
use Database\Seeders\UsuarioTesteSeeder;
use Illuminate\Support\Facades\Hash;

test('usuario teste seeder is idempotent and creates expected users', function () {
    $seeder = new UsuarioTesteSeeder;
    $seeder->run();
    $seeder->run();

    $brigadista = Usuario::query()->where('email', UsuarioTesteSeeder::EMAIL_TESTE)->first();

    expect($brigadista)->not->toBeNull()
        ->and($brigadista->nome)->toBe('Teste')
        ->and($brigadista->cpf)->toBe(UsuarioTesteSeeder::CPF_TESTE)
        ->and(Hash::check('12345678', $brigadista->senha_hash))->toBeTrue();

    $gestor = Usuario::query()->where('email', UsuarioTesteSeeder::EMAIL_GESTOR_TESTE)->first();

    expect($gestor)->not->toBeNull()
        ->and($gestor->nome)->toBe('Gestor Dev')
        ->and($gestor->cpf)->toBe(UsuarioTesteSeeder::CPF_GESTOR_TESTE)
        ->and(Hash::check('12345678', $gestor->senha_hash))->toBeTrue();

    $admin = Usuario::query()->where('email', UsuarioTesteSeeder::EMAIL_ADMINISTRADOR_TESTE)->first();

    expect($admin)->not->toBeNull()
        ->and($admin->nome)->toBe('Administrador Dev')
        ->and($admin->cpf)->toBe(UsuarioTesteSeeder::CPF_ADMINISTRADOR_TESTE)
        ->and(Hash::check('12345678', $admin->senha_hash))->toBeTrue();

    expect(Usuario::query()->whereIn('email', [
        UsuarioTesteSeeder::EMAIL_TESTE,
        UsuarioTesteSeeder::EMAIL_GESTOR_TESTE,
        UsuarioTesteSeeder::EMAIL_ADMINISTRADOR_TESTE,
    ])->count())->toBe(3);
});
