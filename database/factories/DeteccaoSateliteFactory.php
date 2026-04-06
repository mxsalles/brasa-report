<?php

namespace Database\Factories;

use App\Models\DeteccaoSatelite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeteccaoSatelite>
 */
class DeteccaoSateliteFactory extends Factory
{
    protected $model = DeteccaoSatelite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'latitude' => fake()->latitude(-20, -10),
            'longitude' => fake()->longitude(-60, -50),
            'detectado_em' => fake()->dateTimeBetween('-10 days', 'now'),
            'confianca' => fake()->randomFloat(2, 0, 100),
            'fonte' => 'NASA FIRMS',
        ];
    }
}
