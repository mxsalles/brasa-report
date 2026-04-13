<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\FuncaoUsuario;
use App\Models\Usuario;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUsuario implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): Usuario
    {
        Validator::make($input, [
            'nome' => $this->nomeRules(),
            'email' => $this->emailRules(),
            'cpf' => [
                'required',
                'string',
                'size:11',
                'regex:/^\d{11}$/',
                Rule::unique('usuarios', 'cpf'),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return Usuario::create([
            'nome' => $input['nome'],
            'email' => $input['email'],
            'cpf' => $input['cpf'],
            'senha_hash' => $input['password'],
            'funcao' => FuncaoUsuario::Brigadista,
        ]);
    }
}
