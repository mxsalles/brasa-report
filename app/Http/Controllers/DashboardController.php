<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Incendio;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard', [
            'dados' => $this->getDados(),
        ]);
    }

    public function dados(): JsonResponse
    {
        return response()->json($this->getDados());
    }

    /**
     * @return array{
     *     incendios: array{
     *         total: int,
     *         ativos: int,
     *         contidos: int,
     *         resolvidos: int
     *     },
     *     alertas: array{
     *         total: int,
     *         nao_entregues: int
     *     },
     *     ultimo_registro: mixed
     * }
     */
    private function getDados(): array
    {
        return [
            'incendios' => [
                'total' => Incendio::query()->count(),
                'ativos' => Incendio::query()->where('status', 'ativo')->count(),
                'contidos' => Incendio::query()->where('status', 'contido')->count(),
                'resolvidos' => Incendio::query()->where('status', 'resolvido')->count(),
            ],
            'alertas' => [
                'total' => Alerta::query()->count(),
                'nao_entregues' => Alerta::query()->where('entregue', false)->count(),
            ],
            'ultimo_registro' => Incendio::query()->latest('detectado_em')->value('detectado_em'),
        ];
    }
}
