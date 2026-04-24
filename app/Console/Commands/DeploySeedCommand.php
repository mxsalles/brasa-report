<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeploySeedCommand extends Command
{
    protected $signature = 'app:deploy-seed';

    protected $description = 'Roda o DatabaseSeeder (db:seed --force --no-interaction; uso no entrypoint de deploy).';

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
