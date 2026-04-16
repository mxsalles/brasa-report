<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Mirrors production password policy from AppServiceProvider::configureDefaults().
 */
test('production password default accepts eight characters without complexity rules', function () {
    Password::defaults(fn (): ?Password => Password::min(8));

    try {
        $validator = Validator::make(
            [
                'password' => 'aaaaaaaa',
                'password_confirmation' => 'aaaaaaaa',
            ],
            [
                'password' => ['required', 'string', Password::default(), 'confirmed'],
            ]
        );

        expect($validator->passes())->toBeTrue();
    } finally {
        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
            : null
        );
    }
});

test('production password default rejects passwords shorter than eight characters', function () {
    Password::defaults(fn (): ?Password => Password::min(8));

    try {
        $validator = Validator::make(
            [
                'password' => 'aaaaaaa',
                'password_confirmation' => 'aaaaaaa',
            ],
            [
                'password' => ['required', 'string', Password::default(), 'confirmed'],
            ]
        );

        expect($validator->fails())->toBeTrue();
    } finally {
        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
            : null
        );
    }
});
