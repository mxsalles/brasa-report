<?php

use App\Models\Usuario;

test('usuario model is configured for uuid primary key and table name', function () {
    $usuario = new Usuario;

    expect($usuario->getTable())->toBe('usuarios')
        ->and($usuario->getKeyType())->toBe('string')
        ->and($usuario->getIncrementing())->toBeFalse();
});
