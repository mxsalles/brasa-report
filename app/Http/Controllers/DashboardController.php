<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Incendio;
use App\Services\OpenMeteo\OpenMeteoCurrentWeatherService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly OpenMeteoCurrentWeatherService $openMeteoCurrentWeather,
    ) {}

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
     *     ultimo_registro: mixed,
     *     incendios_recentes: list<array{
     *         id: string,
     *         status: string,
     *         nivel_risco: string,
     *         descricao: string,
     *         area_nome: string,
     *         registrado_por: string,
     *         detectado_em: mixed
     *     }>,
     *     alertas_recentes: list<array{
     *         id: string,
     *         tipo: string,
     *         mensagem: string,
     *         enviado_em: mixed,
     *         entregue: bool
     *     }>,
     *     clima: array{temperatura_c: float, umidade_pct: int, atualizado_em: string}|null
     * }
     */
    private function getDados(): array
    {
        $incendiosRecentes = Incendio::query()
            ->with(['area', 'usuario', 'localCritico'])
            ->orderByDesc('detectado_em')
            ->limit(6)
            ->get()
            ->map(fn (Incendio $incendio): array => [
                'id' => $incendio->id,
                'status' => $incendio->status->value,
                'nivel_risco' => $incendio->nivel_risco->value,
                'descricao' => $this->descricaoIncendio($incendio),
                'area_nome' => $incendio->area?->nome ?? 'Área não informada',
                'registrado_por' => $incendio->usuario?->nome ?? 'Sistema',
                'detectado_em' => $incendio->detectado_em,
            ])
            ->all();

        $alertasRecentes = Alerta::query()
            ->orderByDesc('enviado_em')
            ->limit(6)
            ->get()
            ->map(fn (Alerta $alerta): array => [
                'id' => $alerta->id,
                'tipo' => $alerta->tipo->value,
                'mensagem' => $alerta->mensagem,
                'enviado_em' => $alerta->enviado_em,
                'entregue' => (bool) $alerta->entregue,
            ])
            ->all();

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
            'incendios_recentes' => $incendiosRecentes,
            'alertas_recentes' => $alertasRecentes,
            'clima' => $this->openMeteoCurrentWeather->obterAtual(),
        ];
    }

    private function descricaoIncendio(Incendio $incendio): string
    {
        if ($incendio->localCritico !== null) {
            return 'Próximo a '.$incendio->localCritico->nome;
        }

        $coords = sprintf('%.4f, %.4f', (float) $incendio->latitude, (float) $incendio->longitude);

        return 'Detecção em '.$coords;
    }
}
