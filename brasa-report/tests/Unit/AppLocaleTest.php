<?php

use Tests\TestCase;

uses(TestCase::class);

test('app locale is configured for Brazilian Portuguese', function () {
    expect(config('app.locale'))->toBe('pt_BR')
        ->and(config('app.fallback_locale'))->toBe('pt_BR')
        ->and(config('app.faker_locale'))->toBe('pt_BR');
});
