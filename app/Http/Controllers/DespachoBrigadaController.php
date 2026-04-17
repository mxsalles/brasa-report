<?php

namespace App\Http\Controllers;

use App\Enums\StatusIncendio;
use App\Http\Requests\DespachoBrigada\FinalizarDespachoRequest;
use App\Http\Requests\DespachoBrigada\RegistrarChegadaRequest;
use App\Http\Requests\DespachoBrigada\StoreDespachoBrigadaRequest;
use App\Http\Resources\DespachoBrigadaResource;
use App\Models\Brigada;
use App\Models\DespachoBrigada;
use App\Models\Incendio;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DespachoBrigadaController extends Controller
{
    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function index(Request $request, Incendio $incendio): JsonResponse
    {
        $query = $incendio->despachosBrigada()
            ->with('brigada')
            ->orderBy('despachado_em', 'desc');

        if ($request->has('finalizado')) {
            if ($request->boolean('finalizado')) {
                $query->whereNotNull('finalizado_em');
            } else {
                $query->whereNull('finalizado_em');
            }
        }

        $despachos = $query->paginate(20);

        return DespachoBrigadaResource::collection($despachos)->response();
    }

    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function show(Incendio $incendio, DespachoBrigada $despacho): DespachoBrigadaResource
    {
        if ($despacho->incendio_id !== $incendio->id) {
            abort(404);
        }

        $despacho->load('brigada');

        return new DespachoBrigadaResource($despacho);
    }

    /**
     * Papéis futuros: gestor, admin
     */
    public function store(StoreDespachoBrigadaRequest $request, Incendio $incendio): JsonResponse
    {
        $dados = $request->validated();

        $brigadaId = $dados['brigada_id'];

        $jaDespachada = DespachoBrigada::query()
            ->where('incendio_id', $incendio->id)
            ->where('brigada_id', $brigadaId)
            ->whereNull('finalizado_em')
            ->exists();

        if ($jaDespachada) {
            return response()->json([
                'message' => 'Esta brigada já possui um despacho em aberto para este incêndio.',
            ], 409);
        }

        $despacho = DespachoBrigada::query()->create([
            'incendio_id' => $incendio->id,
            'brigada_id' => $brigadaId,
            'despachado_em' => now(),
            'observacoes' => $dados['observacoes'] ?? null,
        ]);

        Brigada::query()->whereKey($brigadaId)->update(['disponivel' => false]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'despacho_brigada',
            'entidade_tipo' => 'despachos_brigada',
            'entidade_id' => $despacho->id,
            'dados_json' => null,
        ]);

        $despacho->load('brigada');

        return (new DespachoBrigadaResource($despacho))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function registrarChegada(RegistrarChegadaRequest $request, Incendio $incendio, DespachoBrigada $despacho): JsonResponse
    {
        if ($despacho->incendio_id !== $incendio->id) {
            abort(404);
        }

        if ($despacho->chegada_em !== null) {
            return response()->json([
                'message' => 'Chegada já registrada',
            ], 422);
        }

        $dados = $request->validated();

        $despacho->update([
            'chegada_em' => $dados['chegada_em'],
        ]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'chegada_brigada',
            'entidade_tipo' => 'despachos_brigada',
            'entidade_id' => $despacho->id,
            'dados_json' => null,
        ]);

        $incendio->refresh();

        if ($incendio->status === StatusIncendio::Ativo) {
            $incendio->update(['status' => StatusIncendio::EmCombate]);

            LogAuditoria::query()->create([
                'usuario_id' => $usuario->id,
                'acao' => 'atualizacao_status_incendio',
                'entidade_tipo' => 'incendios',
                'entidade_id' => $incendio->id,
                'dados_json' => [
                    'status_anterior' => StatusIncendio::Ativo->value,
                    'status_novo' => StatusIncendio::EmCombate->value,
                ],
            ]);
        }

        $despacho->load('brigada');

        return (new DespachoBrigadaResource($despacho->fresh()))->response();
    }

    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function finalizar(FinalizarDespachoRequest $request, Incendio $incendio, DespachoBrigada $despacho): JsonResponse
    {
        if ($despacho->incendio_id !== $incendio->id) {
            abort(404);
        }

        if ($despacho->chegada_em === null) {
            return response()->json([
                'message' => 'Brigada ainda não chegou ao local',
            ], 422);
        }

        if ($despacho->finalizado_em !== null) {
            return response()->json([
                'message' => 'Despacho já finalizado',
            ], 422);
        }

        $dados = $request->validated();

        $despacho->finalizado_em = $dados['finalizado_em'];

        if (array_key_exists('observacoes', $dados)) {
            $despacho->observacoes = $dados['observacoes'];
        }

        $despacho->save();

        $despacho->brigada->update(['disponivel' => true]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'finalizacao_despacho',
            'entidade_tipo' => 'despachos_brigada',
            'entidade_id' => $despacho->id,
            'dados_json' => null,
        ]);

        $despacho->load('brigada');

        return (new DespachoBrigadaResource($despacho->fresh()))->response();
    }
}
