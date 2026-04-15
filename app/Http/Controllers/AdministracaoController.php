<?php

namespace App\Http\Controllers;

use App\Enums\FuncaoUsuario;
use App\Http\Resources\UsuarioResource;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdministracaoController extends Controller
{
    public function index(Request $request): Response
    {
        $usuarios = Usuario::query()
            ->with('brigada')
            ->orderBy('nome')
            ->paginate(20)
            ->through(fn (Usuario $usuario): array => (new UsuarioResource($usuario))->toArray($request));

        $logsAuditoria = LogAuditoria::query()
            ->with('usuario')
            ->orderByDesc('criado_em')
            ->paginate(50)
            ->through(function (LogAuditoria $log): array {
                return [
                    'id' => $log->id,
                    'criado_em' => $log->criado_em,
                    'usuario_nome' => $log->usuario?->nome ?? '—',
                    'acao' => $log->acao,
                    'detalhes' => $this->formatarDetalhesLog($log),
                ];
            });

        /** @var Usuario $auth */
        $auth = $request->user();

        return Inertia::render('administracao', [
            'usuarios' => $usuarios,
            'logsAuditoria' => $logsAuditoria,
            'podeGerenciarAdministradores' => $auth->funcao === FuncaoUsuario::Administrador,
            'funcaoAutenticado' => $auth->funcao->value,
        ]);
    }

    private function formatarDetalhesLog(LogAuditoria $log): ?string
    {
        if ($log->dados_json === null) {
            return null;
        }

        $encoded = json_encode($log->dados_json, JSON_UNESCAPED_UNICODE);

        return $encoded === false ? null : $encoded;
    }
}
