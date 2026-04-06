<?php

namespace Database\Factories;

use App\Models\LocalCritico;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LocalCritico>
 */
class LocalCriticoFactory extends Factory
{
    protected $model = LocalCritico::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'nome' => fake()->words(3, true),
            'tipo' => fake()->randomElement(['residencia', 'escola', 'infraestrutura']),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'descricao' => fake()->optional()->sentence(),
        ];
    }
}
