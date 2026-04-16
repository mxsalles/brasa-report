<?php

namespace Database\Factories;

use App\Models\AreaMonitorada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AreaMonitorada>
 */
class AreaMonitoradaFactory extends Factory
{
    protected $model = AreaMonitorada::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->words(3, true),
            'caminho_geopackage' => null,
            'geometria_geojson' => null,
            'importado_em' => now(),
        ];
    }
}
