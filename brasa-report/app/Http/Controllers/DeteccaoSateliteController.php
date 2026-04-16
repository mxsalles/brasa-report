<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeteccaoSatelite\StoreDeteccaoSateliteRequest;
use App\Http\Requests\DeteccaoSatelite\StoreLoteDeteccaoSateliteRequest;
use App\Http\Resources\DeteccaoSateliteResource;
use App\Models\DeteccaoSatelite;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeteccaoSateliteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DeteccaoSatelite::query();

        if ($request->filled('fonte')) {
            $fonte = $request->string('fonte')->value();
            $query->where('fonte', $fonte);
        }

        if ($request->filled('confianca_min')) {
            $confiancaMin = (float) $request->input('confianca_min');
            $query->where('confianca', '>=', $confiancaMin);
        }

        if ($request->filled('de')) {
            $de = $request->string('de')->value();
            $query->whereDate('detectado_em', '>=', $de);
        }

        if ($request->filled('ate')) {
            $ate = $request->string('ate')->value();
            $query->whereDate('detectado_em', '<=', $ate);
        }

        $deteccoes = $query
            ->orderByDesc('detectado_em')
            ->paginate(20);

        return DeteccaoSateliteResource::collection($deteccoes)->response();
    }

    public function show(DeteccaoSatelite $deteccao): DeteccaoSateliteResource
    {
        return new DeteccaoSateliteResource($deteccao);
    }

    public function store(StoreDeteccaoSateliteRequest $request): JsonResponse
    {
        $dados = $request->validated();
        $dados['fonte'] = $dados['fonte'] ?? 'NASA FIRMS';

        $deteccao = DeteccaoSatelite::query()->create($dados);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'ingestao_deteccao_satelite',
            'entidade_tipo' => 'deteccoes_satelite',
            'entidade_id' => $deteccao->id,
            'dados_json' => null,
        ]);

        return (new DeteccaoSateliteResource($deteccao))
            ->response()
            ->setStatusCode(201);
    }

    public function storeLote(StoreLoteDeteccaoSateliteRequest $request): JsonResponse
    {
        $deteccoes = $request->validated('deteccoes');

        /** @var Usuario $usuario */
        $usuario = $request->user();

        $total = DB::transaction(function () use ($deteccoes, $usuario): int {
            $rows = [];

            foreach ($deteccoes as $deteccao) {
                $rows[] = [
                    'latitude' => $deteccao['latitude'],
                    'longitude' => $deteccao['longitude'],
                    'detectado_em' => CarbonImmutable::parse($deteccao['detectado_em'])->toIso8601String(),
                    'confianca' => $deteccao['confianca'],
                    'fonte' => $deteccao['fonte'] ?? 'NASA FIRMS',
                ];
            }

            DeteccaoSatelite::query()->insert($rows);

            $total = count($rows);

            LogAuditoria::query()->create([
                'usuario_id' => $usuario->id,
                'acao' => 'ingestao_lote_deteccoes_satelite',
                'entidade_tipo' => 'deteccoes_satelite',
                'entidade_id' => null,
                'dados_json' => [
                    'total' => $total,
                ],
            ]);

            return $total;
        });

        return response()->json([
            'total' => $total,
            'mensagem' => 'Lote de detecções de satélite registrado com sucesso.',
        ], 201);
    }
}
