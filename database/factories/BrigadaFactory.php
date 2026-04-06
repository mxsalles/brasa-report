<?php

namespace Database\Factories;

use App\Models\Brigada;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brigada>
 */
class BrigadaFactory extends Factory
{
    protected $model = Brigada::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'nome' => fake()->words(3, true),
            'tipo' => fake()->randomElement(['florestal', 'urbano', 'mist']),
            'latitude_atual' => null,
            'longitude_atual' => null,
            'disponivel' => true,
        ];
    }
}
