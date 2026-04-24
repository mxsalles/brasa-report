<?php

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Deploy seed (routes/console.php)
|--------------------------------------------------------------------------
|
| Dois closures distintos: compartilhar a mesma instância de Closure em dois
| Artisan::command pode fazer com que só um nome fique registrado após
| migrate:fresh / reboot do console em testes.
|
*/

Artisan::command('deploy:seed', function (): int {
    if (! config('deployment.seed_on_deploy')) {
        $this->components->warn('Sementes de deploy desabilitadas (SEED_ON_DEPLOY).');

        return Command::SUCCESS;
    }

    $this->call('db:seed', [
        '--force' => true,
        '--no-interaction' => true,
    ]);

    $this->components->info('Sementes de deploy concluídas.');

    return Command::SUCCESS;
})->purpose('Roda o DatabaseSeeder pós-deploy (db:seed --force).');

Artisan::command('app:deploy-seed', function (): int {
    if (! config('deployment.seed_on_deploy')) {
        $this->components->warn('Sementes de deploy desabilitadas (SEED_ON_DEPLOY).');

        return Command::SUCCESS;
    }

    $this->call('db:seed', [
        '--force' => true,
        '--no-interaction' => true,
    ]);

    $this->components->info('Sementes de deploy concluídas.');

    return Command::SUCCESS;
})->purpose('Mesmo comportamento que deploy:seed (atalho no namespace app).');
