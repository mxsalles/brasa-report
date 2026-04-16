<?php

namespace App\Console\Commands;

use App\Models\AreaMonitorada;
use Database\Seeders\AreaMonitoradaSeeder;
use Illuminate\Console\Command;

class EnsureAreasMonitoradasCommand extends Command
{
    protected $signature = 'areas:ensure';

    protected $description = 'Garante que áreas monitoradas padrão existam (seed quando a tabela estiver vazia).';

    public function handle(): int
    {
        if (AreaMonitorada::query()->exists()) {
            $this->components->info('Áreas monitoradas já existem.');

            return self::SUCCESS;
        }

        $this->call('db:seed', [
            '--class' => AreaMonitoradaSeeder::class,
            '--no-interaction' => true,
        ]);

        $this->components->info('Área padrão Pantanal Geral criada.');

        return self::SUCCESS;
    }
}
