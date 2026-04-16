<?php

namespace App\Concerns;

use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function profileRules(?string $userId = null): array
    {
        return [
            'nome' => $this->nomeRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function nomeRules(): array
    {
        return ['required', 'string', 'max:150'];
    }

    /**
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function emailRules(?string $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique('usuarios', 'email')
                : Rule::unique('usuarios', 'email')->ignore($userId),
        ];
    }
}
