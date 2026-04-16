<?php

namespace Database\Factories;

use App\Enums\FuncaoUsuario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => null,
            'cpf' => fake()->unique()->numerify('###########'),
            'senha_hash' => static::$password ??= Hash::make('password'),
            'funcao' => FuncaoUsuario::User,
            'brigada_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * @return $this
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return $this
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * @return $this
     */
    public function brigadista(): static
    {
        return $this->state(fn (array $attributes): array => [
            'funcao' => FuncaoUsuario::Brigadista,
        ]);
    }

    /**
     * @return $this
     */
    public function gestor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'funcao' => FuncaoUsuario::Gestor,
        ]);
    }

    /**
     * @return $this
     */
    public function administrador(): static
    {
        return $this->state(fn (array $attributes): array => [
            'funcao' => FuncaoUsuario::Administrador,
        ]);
    }

    /**
     * @return $this
     */
    public function bloqueado(): static
    {
        return $this->state(fn (array $attributes): array => [
            'bloqueado' => true,
        ]);
    }
}
