<?php

namespace Database\Factories;

use App\Enums\NivelRiscoIncendio;
use App\Enums\StatusIncendio;
use App\Models\AreaMonitorada;
use App\Models\Incendio;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incendio>
 */
class IncendioFactory extends Factory
{
    protected $model = Incendio::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'latitude' => fake()->latitude(-20, -10),
            'longitude' => fake()->longitude(-60, -50),
            'detectado_em' => fake()->dateTimeBetween('-30 days', 'now'),
            'nivel_risco' => fake()->randomElement(NivelRiscoIncendio::cases()),
            'status' => fake()->randomElement(StatusIncendio::cases()),
            'usuario_id' => Usuario::factory(),
            'area_id' => AreaMonitorada::factory(),
            'local_critico_id' => null,
            'deteccao_satelite_id' => null,
        ];
    }
}
