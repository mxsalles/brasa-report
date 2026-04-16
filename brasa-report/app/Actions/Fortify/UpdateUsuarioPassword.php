<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\Usuario;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUsuarioPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     */
    public function update(Usuario $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'senha_hash' => $input['password'],
        ])->save();
    }
}
