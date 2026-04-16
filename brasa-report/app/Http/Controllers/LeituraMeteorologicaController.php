<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeituraMeteorologica\StoreLeituraMeteorologicaRequest;
use App\Http\Resources\LeituraMeteorologicaResource;
use App\Models\Incendio;
use App\Models\LeituraMeteorologica;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeituraMeteorologicaController extends Controller
{
    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function index(Request $request, Incendio $incendio): JsonResponse
    {
        $query = $incendio->leiturasMeteorologicas()
            ->orderBy('registrado_em', 'desc');

        if ($request->boolean('gera_alerta')) {
            $query->where('gera_alerta', true);
        }

        $leituras = $query->paginate(20);

        return LeituraMeteorologicaResource::collection($leituras)->response();
    }

    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function show(Incendio $incendio, LeituraMeteorologica $leitura): LeituraMeteorologicaResource
    {
        if ($leitura->incendio_id !== $incendio->id) {
            abort(404);
        }

        return new LeituraMeteorologicaResource($leitura);
    }

    /**
     * Papéis futuros: brigadista, gestor, admin
     */
    public function store(StoreLeituraMeteorologicaRequest $request, Incendio $incendio): JsonResponse
    {
        $dados = $request->validated();

        $temperatura = (float) $dados['temperatura'];
        $umidade = (float) $dados['umidade'];
        $geraAlertaAutomatico = $temperatura > 30 || $umidade < 40;
        $geraAlerta = $geraAlertaAutomatico || $request->boolean('gera_alerta');

        $leitura = $incendio->leiturasMeteorologicas()->create([
            'temperatura' => $dados['temperatura'],
            'umidade' => $dados['umidade'],
            'velocidade_vento' => $dados['velocidade_vento'],
            'registrado_em' => $dados['registrado_em'],
            'gera_alerta' => $geraAlerta,
        ]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'registro_leitura_meteorologica',
            'entidade_tipo' => 'leituras_meteorologicas',
            'entidade_id' => $leitura->id,
            'dados_json' => null,
        ]);

        return (new LeituraMeteorologicaResource($leitura))
            ->response()
            ->setStatusCode(201);
    }
}
