<?php

use App\Models\AreaMonitorada;

test('areas:ensure cria Pantanal Geral e não duplica quando já existe', function () {
    expect(AreaMonitorada::query()->count())->toBe(0);

    $this->artisan('areas:ensure')->assertSuccessful();

    expect(AreaMonitorada::query()->where('nome', 'Pantanal Geral')->count())->toBe(1);

    AreaMonitorada::query()->delete();

    AreaMonitorada::factory()->create(['nome' => 'Pantanal Geral']);

    $this->artisan('areas:ensure')->assertSuccessful();

    expect(AreaMonitorada::query()->where('nome', 'Pantanal Geral')->count())->toBe(1);
    expect(AreaMonitorada::query()->count())->toBe(1);
});
