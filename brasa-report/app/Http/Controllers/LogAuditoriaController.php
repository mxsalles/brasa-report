<?php

namespace App\Http\Controllers;

use App\Http\Resources\LogAuditoriaResource;
use App\Models\LogAuditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LogAuditoriaController extends Controller
{
    /**
     * // Papel: admin
     */
    public function index(Request $request): JsonResponse
    {
        $query = LogAuditoria::query()
            ->with('usuario');

        if ($request->filled('acao')) {
            $acao = $request->string('acao')->value();
            $query->where('acao', 'ilike', '%'.$acao.'%');
        }

        if ($request->filled('entidade_tipo')) {
            $entidadeTipo = $request->string('entidade_tipo')->value();
            $query->where('entidade_tipo', $entidadeTipo);
        }

        if ($request->filled('entidade_id')) {
            $entidadeId = $request->string('entidade_id')->value();
            $query->where('entidade_id', $entidadeId);
        }

        if ($request->filled('usuario_id')) {
            $usuarioId = $request->string('usuario_id')->value();
            $query->where('usuario_id', $usuarioId);
        }

        if ($request->filled('de')) {
            $de = Carbon::parse($request->string('de')->value())->startOfDay();
            $query->where('criado_em', '>=', $de);
        }

        if ($request->filled('ate')) {
            $ate = Carbon::parse($request->string('ate')->value())->endOfDay();
            $query->where('criado_em', '<=', $ate);
        }

        $logs = $query
            ->orderByDesc('criado_em')
            ->paginate(50);

        return LogAuditoriaResource::collection($logs)->response();
    }

    /**
     * // Papel: admin
     */
    public function show(LogAuditoria $log): LogAuditoriaResource
    {
        $log->load('usuario');

        return new LogAuditoriaResource($log);
    }
}
