<?php

namespace Database\Factories;

use App\Models\Incendio;
use App\Models\LeituraMeteorologica;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LeituraMeteorologica>
 */
class LeituraMeteorologicaFactory extends Factory
{
    protected $model = LeituraMeteorologica::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'incendio_id' => Incendio::factory(),
            'temperatura' => fake()->randomFloat(2, 20, 35),
            'umidade' => fake()->randomFloat(2, 30, 80),
            'velocidade_vento' => fake()->randomFloat(2, 0, 25),
            'registrado_em' => fake()->dateTimeBetween('-7 days', 'now'),
            'gera_alerta' => false,
        ];
    }
}
