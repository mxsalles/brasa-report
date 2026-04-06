<?php

namespace App\Http\Controllers;

use App\Enums\NivelRiscoIncendio;
use App\Enums\StatusIncendio;
use App\Http\Requests\Incendio\AtualizarRiscoRequest;
use App\Http\Requests\Incendio\AtualizarStatusRequest;
use App\Http\Requests\Incendio\StoreIncendioRequest;
use App\Http\Requests\Incendio\UpdateIncendioRequest;
use App\Http\Resources\IncendioResource;
use App\Models\Incendio;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncendioController extends Controller
{
    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function index(Request $request): JsonResponse
    {
        $query = Incendio::query()
            ->with(['area', 'localCritico', 'deteccaoSatelite']);

        if ($request->filled('status')) {
            $status = $request->string('status')->value();
            $query->where('status', $status);
        }

        if ($request->filled('nivel_risco')) {
            $nivelRisco = $request->string('nivel_risco')->value();
            $query->where('nivel_risco', $nivelRisco);
        }

        if ($request->filled('area_id')) {
            $areaId = $request->string('area_id')->value();
            $query->where('area_id', $areaId);
        }

        if ($request->filled('de')) {
            $de = $request->string('de')->value();
            $query->whereDate('detectado_em', '>=', $de);
        }

        if ($request->filled('ate')) {
            $ate = $request->string('ate')->value();
            $query->whereDate('detectado_em', '<=', $ate);
        }

        $incendios = $query
            ->orderByDesc('detectado_em')
            ->paginate(20);

        return IncendioResource::collection($incendios)->response();
    }

    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function show(Incendio $incendio): IncendioResource
    {
        $incendio->load(['area', 'localCritico', 'deteccaoSatelite', 'usuario']);

        return new IncendioResource($incendio);
    }

    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function store(StoreIncendioRequest $request): JsonResponse
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        $dados = $request->validated();
        $dados['usuario_id'] = $usuario->id;
        $dados['status'] = StatusIncendio::Ativo;

        $incendio = Incendio::query()->create($dados);

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'registro_incendio',
            'entidade_tipo' => 'incendios',
            'entidade_id' => $incendio->id,
            'dados_json' => null,
        ]);

        $incendio->load(['area', 'localCritico', 'deteccaoSatelite', 'usuario']);

        return (new IncendioResource($incendio))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Papéis futuros: gestor, admin
     */
    public function update(UpdateIncendioRequest $request, Incendio $incendio): IncendioResource
    {
        $incendio->update($request->validated());

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'atualizacao_incendio',
            'entidade_tipo' => 'incendios',
            'entidade_id' => $incendio->id,
            'dados_json' => null,
        ]);

        return new IncendioResource($incendio->fresh(['area', 'localCritico', 'deteccaoSatelite', 'usuario']));
    }

    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function atualizarStatus(AtualizarStatusRequest $request, Incendio $incendio): IncendioResource
    {
        $statusAnterior = $incendio->status;
        $statusNovo = StatusIncendio::from($request->validated('status'));

        $incendio->update([
            'status' => $statusNovo,
        ]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'atualizacao_status_incendio',
            'entidade_tipo' => 'incendios',
            'entidade_id' => $incendio->id,
            'dados_json' => [
                'status_anterior' => $statusAnterior->value,
                'status_novo' => $statusNovo->value,
            ],
        ]);

        return new IncendioResource($incendio->fresh(['area', 'localCritico', 'deteccaoSatelite', 'usuario']));
    }

    /**
     * Papéis futuros: gestor, admin
     */
    public function atualizarRisco(AtualizarRiscoRequest $request, Incendio $incendio): IncendioResource
    {
        $riscoAnterior = $incendio->nivel_risco;
        $riscoNovo = NivelRiscoIncendio::from($request->validated('nivel_risco'));

        $incendio->update([
            'nivel_risco' => $riscoNovo,
        ]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'atualizacao_risco_incendio',
            'entidade_tipo' => 'incendios',
            'entidade_id' => $incendio->id,
            'dados_json' => [
                'nivel_risco_anterior' => $riscoAnterior->value,
                'nivel_risco_novo' => $riscoNovo->value,
            ],
        ]);

        return new IncendioResource($incendio->fresh(['area', 'localCritico', 'deteccaoSatelite', 'usuario']));
    }
}
