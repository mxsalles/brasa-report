<?php

namespace App\Services\OpenMeteo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OpenMeteoCurrentWeatherService
{
    /**
     * @return array{
     *     temperatura_c: float,
     *     umidade_pct: int,
     *     atualizado_em: string
     * }|null
     */
    public function obterAtual(?float $latitude = null, ?float $longitude = null): ?array
    {
        $baseUrl = rtrim((string) config('services.open_meteo.base_url', 'https://api.open-meteo.com/v1'), '/');

        $latitude ??= (float) config('services.open_meteo.latitude');
        $longitude ??= (float) config('services.open_meteo.longitude');

        $cacheKey = sprintf('openmeteo:current:%s:%s', $latitude, $longitude);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($baseUrl, $latitude, $longitude): ?array {
            try {
                $response = Http::timeout(5)
                    ->retry(1, 100)
                    ->get($baseUrl.'/forecast', [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'current' => 'temperature_2m,relative_humidity_2m',
                        'timezone' => 'auto',
                    ]);
            } catch (ConnectionException) {
                return null;
            }

            if (! $response->ok()) {
                return null;
            }

            $current = $response->json('current');
            if (! is_array($current)) {
                return null;
            }

            $temperatura = $current['temperature_2m'] ?? null;
            $umidade = $current['relative_humidity_2m'] ?? null;
            $time = $current['time'] ?? null;

            if (! is_numeric($temperatura) || ! is_numeric($umidade) || ! is_string($time) || $time === '') {
                return null;
            }

            return [
                'temperatura_c' => (float) $temperatura,
                'umidade_pct' => (int) round((float) $umidade),
                'atualizado_em' => $time,
            ];
        });
    }
}
