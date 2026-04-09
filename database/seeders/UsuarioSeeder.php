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
            ['email' => 'brigadista@caninde.dev'],
            [
                'nome' => 'Brigadista Teste',
                'cpf' => '11111111111',
                'senha_hash' => $senhaHash,
                'funcao' => FuncaoUsuario::Brigadista,
                'brigada_id' => null,
            ]
        );

        Usuario::updateOrCreate(
            ['email' => 'gestor@caninde.dev'],
            [
                'nome' => 'Gestor Teste',
                'cpf' => '22222222222',
                'senha_hash' => $senhaHash,
                'funcao' => FuncaoUsuario::Gestor,
                'brigada_id' => null,
            ]
        );

        Usuario::updateOrCreate(
            ['email' => 'admin@caninde.dev'],
            [
                'nome' => 'Admin Teste',
                'cpf' => '33333333333',
                'senha_hash' => $senhaHash,
                'funcao' => FuncaoUsuario::Admin,
                'brigada_id' => null,
            ]
        );
    }
}
