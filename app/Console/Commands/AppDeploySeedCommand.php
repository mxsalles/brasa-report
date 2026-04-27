<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppDeploySeedCommand extends Command
{
    protected $signature = 'app:deploy-seed';

    protected $description = 'Mesmo comportamento que deploy:seed (atalho no namespace app).';

    public function handle(): int
    {
        if (! config('deployment.seed_on_deploy')) {
            $this->components->warn('Sementes de deploy desabilitadas (SEED_ON_DEPLOY).');

            return self::SUCCESS;
        }

        $this->call('db:seed', [
            '--force' => true,
            '--no-interaction' => true,
        ]);

        $this->components->info('Sementes de deploy concluídas.');

        return self::SUCCESS;
    }
}
