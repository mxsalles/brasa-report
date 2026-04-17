<?php

use App\Models\Usuario;
use Database\Seeders\UsuarioSeeder;

test('usuario seeder is idempotent and creates four development users', function () {
    $seeder = new UsuarioSeeder;
    $seeder->run();
    $seeder->run();

    expect(Usuario::query()->count())->toBe(4);

    expect(Usuario::query()->where('email', 'user@brasa.dev')->exists())->toBeTrue();
    expect(Usuario::query()->where('email', 'brigadista@brasa.dev')->exists())->toBeTrue();
    expect(Usuario::query()->where('email', 'gestor@brasa.dev')->exists())->toBeTrue();
    expect(Usuario::query()->where('email', 'admin@brasa.dev')->exists())->toBeTrue();
});
