<?php

namespace App\Http\Controllers;

use App\Http\Requests\AreaMonitorada\StoreAreaMonitoradaRequest;
use App\Http\Requests\AreaMonitorada\UpdateAreaMonitoradaRequest;
use App\Http\Resources\AreaMonitoradaResource;
use App\Models\AreaMonitorada;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use App\Services\GeoPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AreaMonitoradaController extends Controller
{
    public function __construct(
        private GeoPackageService $geoPackageService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = AreaMonitorada::query();

        if ($request->filled('nome')) {
            $nome = $request->string('nome')->value();
            $query->where('nome', 'ilike', '%'.$nome.'%');
        }

        $areas = $query->paginate(20);

        return AreaMonitoradaResource::collection($areas)->response();
    }

    public function show(AreaMonitorada $area): AreaMonitoradaResource
    {
        return new AreaMonitoradaResource($area);
    }

    public function store(StoreAreaMonitoradaRequest $request): JsonResponse
    {
        try {
            $resultado = $this->geoPackageService->extrairWkt($request->file('geopackage'));
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Não foi possível processar o GeoPackage.',
            ], 422);
        }

        $area = AreaMonitorada::query()->create([
            'nome' => $request->validated()['nome'],
            'caminho_geopackage' => $resultado['caminho'],
            'geometria_wkt' => $resultado['wkt'],
            'importado_em' => now(),
        ]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'criacao_area_monitorada',
            'entidade_tipo' => 'areas_monitoradas',
            'entidade_id' => $area->id,
            'dados_json' => null,
        ]);

        return (new AreaMonitoradaResource($area))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAreaMonitoradaRequest $request, AreaMonitorada $area): AreaMonitoradaResource
    {
        $area->update($request->validated());

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'atualizacao_area_monitorada',
            'entidade_tipo' => 'areas_monitoradas',
            'entidade_id' => $area->id,
            'dados_json' => null,
        ]);

        return new AreaMonitoradaResource($area->fresh());
    }

    public function destroy(Request $request, AreaMonitorada $area): JsonResponse|Response
    {
        if ($area->incendios()->exists()) {
            return response()->json([
                'message' => 'Não é possível remover uma área monitorada que possui incêndios vinculados.',
            ], 409);
        }

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'remocao_area_monitorada',
            'entidade_tipo' => 'areas_monitoradas',
            'entidade_id' => $area->id,
            'dados_json' => null,
        ]);

        if ($area->caminho_geopackage !== null && $area->caminho_geopackage !== '') {
            Storage::disk('local')->delete($area->caminho_geopackage);
        }

        $area->delete();

        return response()->noContent();
    }
}
