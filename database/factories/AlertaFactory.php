<?php

namespace Database\Factories;

use App\Enums\TipoAlerta;
use App\Models\Alerta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Alerta>
 */
class AlertaFactory extends Factory
{
    protected $model = Alerta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'tipo' => fake()->randomElement(TipoAlerta::cases()),
            'mensagem' => fake()->sentence(),
            'origem_id' => (string) Str::uuid(),
            'origem_tabela' => fake()->randomElement(['leituras_meteorologicas', 'deteccoes_satelite', 'incendios']),
            'enviado_em' => fake()->dateTimeBetween('-30 days', 'now'),
            'entregue' => false,
        ];
    }
}
