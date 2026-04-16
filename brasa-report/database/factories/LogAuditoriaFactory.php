<?php

namespace Database\Factories;

use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogAuditoria>
 */
class LogAuditoriaFactory extends Factory
{
    protected $model = LogAuditoria::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'acao' => fake()->randomElement([
                'login',
                'logout',
                'criacao_usuario',
                'atualizacao_usuario',
            ]),
            'entidade_tipo' => fake()->randomElement(['usuarios', 'brigadas', 'incendios']),
            'entidade_id' => (string) fake()->uuid(),
            'dados_json' => [
                'exemplo' => true,
            ],
        ];
    }

    public function semUsuario(): static
    {
        return $this->state(fn () => [
            'usuario_id' => null,
        ]);
    }
}
