<?php

namespace Database\Seeders;

use App\Models\AreaMonitorada;
use Illuminate\Database\Seeder;

class AreaMonitoradaSeeder extends Seeder
{
    public function run(): void
    {
        AreaMonitorada::updateOrCreate(
            ['nome' => 'Pantanal Geral'],
            [
                'nome' => 'Pantanal Geral',
                'geometria_geojson' => null,
                'caminho_geopackage' => null,
            ]
        );
    }
}
