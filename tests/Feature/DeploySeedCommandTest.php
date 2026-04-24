<?php

use App\Models\AreaMonitorada;
use App\Models\Usuario;

test('app:deploy-seed aplica o DatabaseSeeder e finaliza com sucesso', function () {
    $this->artisan('app:deploy-seed')->assertSuccessful();

    expect(AreaMonitorada::query()->where('nome', 'Pantanal Geral')->exists())->toBeTrue();
    expect(Usuario::query()->where('email', 'admin@brasa.dev')->exists())->toBeTrue();
});

test('app:deploy-seed não aplica sementes quando SEED_ON_DEPLOY está falso', function () {
    config(['deployment.seed_on_deploy' => false]);

    $this->artisan('app:deploy-seed')->assertSuccessful();

    expect(AreaMonitorada::query()->count())->toBe(0);
    expect(Usuario::query()->count())->toBe(0);
});

test('deploy:seed aplica o DatabaseSeeder como app:deploy-seed', function () {
    $this->artisan('deploy:seed')->assertSuccessful();

    expect(AreaMonitorada::query()->where('nome', 'Pantanal Geral')->exists())->toBeTrue();
    expect(Usuario::query()->where('email', 'admin@brasa.dev')->exists())->toBeTrue();
});
