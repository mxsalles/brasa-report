<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocalCritico\StoreLocalCriticoRequest;
use App\Http\Requests\LocalCritico\UpdateLocalCriticoRequest;
use App\Http\Resources\LocalCriticoResource;
use App\Models\LocalCritico;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LocalCriticoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LocalCritico::query();

        if ($request->filled('tipo')) {
            $tipo = $request->string('tipo')->value();
            $query->where('tipo', $tipo);
        }

        if ($request->filled('nome')) {
            $nome = $request->string('nome')->value();
            $query->where('nome', 'ilike', '%'.$nome.'%');
        }

        $locais = $query->paginate(20);

        return LocalCriticoResource::collection($locais)->response();
    }

    public function show(LocalCritico $local): LocalCriticoResource
    {
        return new LocalCriticoResource($local);
    }

    public function store(StoreLocalCriticoRequest $request): JsonResponse
    {
        $local = LocalCritico::query()->create($request->validated());

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'criacao_local_critico',
            'entidade_tipo' => 'locais_criticos',
            'entidade_id' => $local->id,
            'dados_json' => null,
        ]);

        return (new LocalCriticoResource($local))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateLocalCriticoRequest $request, LocalCritico $local): LocalCriticoResource
    {
        $local->update($request->validated());

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'atualizacao_local_critico',
            'entidade_tipo' => 'locais_criticos',
            'entidade_id' => $local->id,
            'dados_json' => null,
        ]);

        return new LocalCriticoResource($local->fresh());
    }

    public function destroy(Request $request, LocalCritico $local): JsonResponse|Response
    {
        if ($local->incendios()->exists()) {
            return response()->json([
                'message' => 'Não é possível remover um local crítico que possui incêndios vinculados.',
            ], 409);
        }

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'remocao_local_critico',
            'entidade_tipo' => 'locais_criticos',
            'entidade_id' => $local->id,
            'dados_json' => null,
        ]);

        $local->delete();

        return response()->noContent();
    }
}
