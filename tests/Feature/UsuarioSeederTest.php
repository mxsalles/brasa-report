<?php

use App\Models\Usuario;
use Database\Seeders\UsuarioSeeder;

test('usuario seeder is idempotent and creates three development users', function () {
    $seeder = new UsuarioSeeder;
    $seeder->run();
    $seeder->run();

    expect(Usuario::query()->count())->toBe(3);

    expect(Usuario::query()->where('email', 'brigadista@caninde.dev')->exists())->toBeTrue();
    expect(Usuario::query()->where('email', 'gestor@caninde.dev')->exists())->toBeTrue();
    expect(Usuario::query()->where('email', 'admin@caninde.dev')->exists())->toBeTrue();
});
