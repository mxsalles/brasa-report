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
| Registrado aqui (além de via diretório app/Console/Commands) para garantir
| que os comandos existam no namespace "deploy" em qualquer bootstrap Artisan,
| inclusive quando o discovery de classes falha no ambiente (ex.: hooks Render).
|
*/

$runDeploySeed = function (): int {
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
};

Artisan::command('deploy:seed', $runDeploySeed)
    ->purpose('Roda o DatabaseSeeder pós-deploy (db:seed --force).');

Artisan::command('app:deploy-seed', $runDeploySeed)
    ->purpose('Mesmo comportamento que deploy:seed (atalho no namespace app).');
