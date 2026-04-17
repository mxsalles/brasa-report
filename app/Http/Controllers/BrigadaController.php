<?php

namespace App\Http\Controllers;

use App\Http\Requests\Brigada\AtualizarLocalizacaoBrigadaRequest;
use App\Http\Requests\Brigada\StoreBrigadaRequest;
use App\Http\Requests\Brigada\UpdateBrigadaRequest;
use App\Http\Resources\BrigadaResource;
use App\Models\Brigada;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BrigadaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Brigada::query()
            ->withCount('usuarios');

        if ($request->has('disponivel')) {
            $query->where('disponivel', $request->boolean('disponivel'));
        }

        $brigadas = $query->paginate(20);

        return BrigadaResource::collection($brigadas)->response();
    }

    public function show(Brigada $brigada): BrigadaResource
    {
        $brigada->load('usuarios');

        return new BrigadaResource($brigada);
    }

    public function store(StoreBrigadaRequest $request): JsonResponse
    {
        $brigada = Brigada::query()->create($request->validated());

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'criacao_brigada',
            'entidade_tipo' => 'brigadas',
            'entidade_id' => $brigada->id,
            'dados_json' => null,
        ]);

        return (new BrigadaResource($brigada))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBrigadaRequest $request, Brigada $brigada): BrigadaResource
    {
        $brigada->update($request->validated());

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'atualizacao_brigada',
            'entidade_tipo' => 'brigadas',
            'entidade_id' => $brigada->id,
            'dados_json' => null,
        ]);

        return new BrigadaResource($brigada->fresh());
    }

    public function destroy(Request $request, Brigada $brigada): JsonResponse|Response
    {
        if ($brigada->usuarios()->exists()) {
            return response()->json([
                'message' => 'Não é possível remover uma brigada que possui membros vinculados.',
            ], 409);
        }

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'remocao_brigada',
            'entidade_tipo' => 'brigadas',
            'entidade_id' => $brigada->id,
            'dados_json' => null,
        ]);

        $brigada->delete();

        return response()->noContent();
    }

    public function restore(Request $request, string $id): BrigadaResource
    {
        $brigada = Brigada::onlyTrashed()->findOrFail($id);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'restauracao_brigada',
            'entidade_tipo' => 'brigadas',
            'entidade_id' => $brigada->id,
            'dados_json' => null,
        ]);

        $brigada->restore();

        return new BrigadaResource($brigada->fresh(['usuarios']));
    }

    public function atualizarLocalizacao(AtualizarLocalizacaoBrigadaRequest $request, Brigada $brigada): BrigadaResource
    {
        $dados = $request->validated();

        $brigada->update([
            'latitude_atual' => $dados['latitude_atual'],
            'longitude_atual' => $dados['longitude_atual'],
        ]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'atualizacao_localizacao_brigada',
            'entidade_tipo' => 'brigadas',
            'entidade_id' => $brigada->id,
            'dados_json' => [
                'latitude_atual' => $dados['latitude_atual'],
                'longitude_atual' => $dados['longitude_atual'],
            ],
        ]);

        return new BrigadaResource($brigada->fresh());
    }
}
