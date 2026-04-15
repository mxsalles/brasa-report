<?php

namespace Database\Seeders;

use App\Enums\FuncaoUsuario;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioTesteSeeder extends Seeder
{
    public const string EMAIL_TESTE = 'teste@gmail.com';

    public const string EMAIL_GESTOR_TESTE = 'gestor@gmail.com';

    public const string EMAIL_ADMINISTRADOR_TESTE = 'admin@gmail.com';

    /**
     * CPF armazenado somente com dígitos (11), conforme validação da aplicação.
     */
    public const string CPF_TESTE = '12345678954';

    public const string CPF_GESTOR_TESTE = '99887766554';

    public const string CPF_ADMINISTRADOR_TESTE = '88776655443';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $senhaDev = Hash::make('12345678');

        Usuario::updateOrCreate(
            ['email' => self::EMAIL_TESTE],
            [
                'nome' => 'Teste',
                'cpf' => self::CPF_TESTE,
                'senha_hash' => $senhaDev,
                'funcao' => FuncaoUsuario::Brigadista,
                'brigada_id' => null,
                'email_verified_at' => now(),
            ]
        );

        Usuario::updateOrCreate(
            ['email' => self::EMAIL_GESTOR_TESTE],
            [
                'nome' => 'Gestor Dev',
                'cpf' => self::CPF_GESTOR_TESTE,
                'senha_hash' => $senhaDev,
                'funcao' => FuncaoUsuario::Gestor,
                'brigada_id' => null,
                'email_verified_at' => now(),
            ]
        );

        Usuario::updateOrCreate(
            ['email' => self::EMAIL_ADMINISTRADOR_TESTE],
            [
                'nome' => 'Administrador Dev',
                'cpf' => self::CPF_ADMINISTRADOR_TESTE,
                'senha_hash' => $senhaDev,
                'funcao' => FuncaoUsuario::Administrador,
                'brigada_id' => null,
                'email_verified_at' => now(),
            ]
        );
    }
}
