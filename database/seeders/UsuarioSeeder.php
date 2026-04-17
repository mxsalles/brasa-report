<?php

namespace Database\Seeders;

use App\Enums\FuncaoUsuario;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $senhaHash = Hash::make('password');

        Usuario::updateOrCreate(
            ['email' => 'user@brasa.dev'],
            [
                'nome' => 'Usuário Teste',
                'cpf' => '44444444444',
                'senha_hash' => $senhaHash,
                'funcao' => FuncaoUsuario::User,
                'brigada_id' => null,
                'email_verified_at' => now(),
            ]
        );

        Usuario::updateOrCreate(
            ['email' => 'brigadista@brasa.dev'],
            [
                'nome' => 'Brigadista Teste',
                'cpf' => '11111111111',
                'senha_hash' => $senhaHash,
                'funcao' => FuncaoUsuario::Brigadista,
                'brigada_id' => null,
                'email_verified_at' => now(),
            ]
        );

        Usuario::updateOrCreate(
            ['email' => 'gestor@brasa.dev'],
            [
                'nome' => 'Gestor Teste',
                'cpf' => '22222222222',
                'senha_hash' => $senhaHash,
                'funcao' => FuncaoUsuario::Gestor,
                'brigada_id' => null,
                'email_verified_at' => now(),
            ]
        );

        Usuario::updateOrCreate(
            ['email' => 'admin@brasa.dev'],
            [
                'nome' => 'Admin Teste',
                'cpf' => '33333333333',
                'senha_hash' => $senhaHash,
                'funcao' => FuncaoUsuario::Administrador,
                'brigada_id' => null,
                'email_verified_at' => now(),
            ]
        );
    }
}
