<?php

namespace Database\Factories;

use App\Models\Brigada;
use App\Models\DespachoBrigada;
use App\Models\Incendio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DespachoBrigada>
 */
class DespachoBrigadaFactory extends Factory
{
    protected $model = DespachoBrigada::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'incendio_id' => Incendio::factory(),
            'brigada_id' => Brigada::factory(),
            'despachado_em' => fake()->dateTimeBetween('-7 days', 'now'),
            'chegada_em' => null,
            'finalizado_em' => null,
            'observacoes' => null,
        ];
    }
}
