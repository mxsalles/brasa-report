<?php

use App\Models\AreaMonitorada;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('areas:ensure cria Pantanal Geral quando a tabela está vazia', function () {
    expect(AreaMonitorada::query()->count())->toBe(0);

    $this->artisan('areas:ensure')->assertSuccessful();

    expect(AreaMonitorada::query()->where('nome', 'Pantanal Geral')->count())->toBe(1);
});

test('areas:ensure não duplica quando a área já existe', function () {
    AreaMonitorada::factory()->create(['nome' => 'Pantanal Geral']);

    $this->artisan('areas:ensure')->assertSuccessful();

    expect(AreaMonitorada::query()->where('nome', 'Pantanal Geral')->count())->toBe(1);
    expect(AreaMonitorada::query()->count())->toBe(1);
});
