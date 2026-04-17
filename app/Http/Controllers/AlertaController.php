<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlertaResource;
use App\Models\Alerta;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertaController extends Controller
{
    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function index(Request $request): JsonResponse
    {
        $query = Alerta::query();

        if ($request->has('entregue')) {
            $query->where('entregue', $request->boolean('entregue'));
        }

        if ($request->filled('tipo')) {
            $tipo = $request->string('tipo')->value();
            $query->where('tipo', $tipo);
        }

        if ($request->filled('origem_tabela')) {
            $origemTabela = $request->string('origem_tabela')->value();
            $query->where('origem_tabela', $origemTabela);
        }

        $alertas = $query
            ->orderByDesc('enviado_em')
            ->paginate(20);

        $alertas->getCollection()->loadMorph('origem', Alerta::origemMorphWith());

        return AlertaResource::collection($alertas)->response();
    }

    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function show(Alerta $alerta): AlertaResource
    {
        $alerta->loadMorph('origem', Alerta::origemMorphWith());

        return new AlertaResource($alerta);
    }

    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function marcarEntregue(Request $request, Alerta $alerta): JsonResponse|AlertaResource
    {
        if ($alerta->entregue) {
            return response()->json([
                'message' => 'Alerta já marcado como entregue',
            ], 422);
        }

        $alerta->update([
            'entregue' => true,
        ]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'alerta_marcado_entregue',
            'entidade_tipo' => 'alertas',
            'entidade_id' => $alerta->id,
            'dados_json' => null,
        ]);

        $fresh = $alerta->fresh();
        $fresh->loadMorph('origem', Alerta::origemMorphWith());

        return new AlertaResource($fresh);
    }
}
