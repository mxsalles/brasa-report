<?php

namespace Database\Seeders;

use App\Enums\FuncaoUsuario;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioTesteSeeder extends Seeder
{
    public const string EMAIL_TESTE = 'teste@gmail.com';

    /**
     * CPF armazenado somente com dígitos (11), conforme validação da aplicação.
     */
    public const string CPF_TESTE = '12345678954';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        Usuario::updateOrCreate(
            ['email' => self::EMAIL_TESTE],
            [
                'nome' => 'Teste',
                'cpf' => self::CPF_TESTE,
                'senha_hash' => Hash::make('12345678'),
                'funcao' => FuncaoUsuario::Brigadista,
                'brigada_id' => null,
                'email_verified_at' => now(),
            ]
        );
    }
}
