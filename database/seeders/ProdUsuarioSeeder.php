<?php

namespace Database\Seeders;

use App\Enums\FuncaoUsuario;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProdUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::updateOrCreate(
            ['email' => config('seeder.gestor_email')],
            [
                'nome' => config('seeder.gestor_nome'),
                'cpf' => config('seeder.gestor_cpf'),
                'senha_hash' => Hash::make(config('seeder.gestor_senha')),
                'funcao' => FuncaoUsuario::Gestor,
                'brigada_id' => null,
                'email_verified_at' => now(),
            ]
        );

        Usuario::updateOrCreate(
            ['email' => config('seeder.admin_email')],
            [
                'nome' => config('seeder.admin_nome'),
                'cpf' => config('seeder.admin_cpf'),
                'senha_hash' => Hash::make(config('seeder.admin_senha')),
                'funcao' => FuncaoUsuario::Administrador,
                'brigada_id' => null,
                'email_verified_at' => now(),
            ]
        );
    }
}
