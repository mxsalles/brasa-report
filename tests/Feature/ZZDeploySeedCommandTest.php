<?php

use App\Models\AreaMonitorada;
use App\Models\Usuario;

test('deploy seed commands aplicam DatabaseSeeder e respeitam SEED_ON_DEPLOY', function () {
    $this->artisan('app:deploy-seed')->assertSuccessful();

    expect(AreaMonitorada::query()->where('nome', 'Pantanal Geral')->exists())->toBeTrue();
    expect(Usuario::query()->where('email', 'admin@brasa.dev')->exists())->toBeTrue();

    $this->artisan('migrate:fresh', [
        '--force' => true,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $this->refreshApplication();

    config(['deployment.seed_on_deploy' => false]);

    $this->artisan('app:deploy-seed')->assertSuccessful();

    expect(AreaMonitorada::query()->count())->toBe(0);
    expect(Usuario::query()->count())->toBe(0);

    config(['deployment.seed_on_deploy' => true]);

    $this->artisan('migrate:fresh', [
        '--force' => true,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $this->refreshApplication();

    $this->artisan('deploy:seed')->assertSuccessful();

    expect(AreaMonitorada::query()->where('nome', 'Pantanal Geral')->exists())->toBeTrue();
    expect(Usuario::query()->where('email', 'admin@brasa.dev')->exists())->toBeTrue();
});
