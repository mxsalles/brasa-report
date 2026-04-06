<?php

namespace App\Http\Controllers;

use App\Http\Requests\Usuario\AtualizarBrigadaRequest;
use App\Http\Requests\Usuario\AtualizarFuncaoRequest;
use App\Http\Requests\Usuario\StoreUsuarioRequest;
use App\Http\Requests\Usuario\UpdateUsuarioRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UsuarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Usuario::query()
            ->with('brigada');

        if ($request->filled('funcao')) {
            $funcao = $request->string('funcao')->value();
            $query->where('funcao', $funcao);
        }

        if ($request->filled('brigada_id')) {
            $brigadaId = $request->string('brigada_id')->value();
            $query->where('brigada_id', $brigadaId);
        }

        if ($request->filled('nome')) {
            $nome = $request->string('nome')->value();
            $query->where('nome', 'ilike', '%'.$nome.'%');
        }

        $usuarios = $query->paginate(20);

        return UsuarioResource::collection($usuarios)->response();
    }

    public function show(Usuario $usuario): UsuarioResource
    {
        $usuario->load('brigada');

        return new UsuarioResource($usuario);
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $dados = $request->validated();

        $novoUsuario = Usuario::query()->create([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'cpf' => $dados['cpf'],
            'senha_hash' => bcrypt($dados['senha']),
            'funcao' => $dados['funcao'],
            'brigada_id' => $dados['brigada_id'] ?? null,
        ]);

        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'criacao_usuario',
            'entidade_tipo' => 'usuarios',
            'entidade_id' => $novoUsuario->id,
            'dados_json' => null,
        ]);

        return (new UsuarioResource($novoUsuario))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateUsuarioRequest $request, Usuario $usuario): UsuarioResource
    {
        $usuario->update($request->validated());

        /** @var Usuario $autor */
        $autor = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $autor->id,
            'acao' => 'atualizacao_usuario',
            'entidade_tipo' => 'usuarios',
            'entidade_id' => $usuario->id,
            'dados_json' => null,
        ]);

        return new UsuarioResource($usuario->fresh());
    }

    public function destroy(Request $request, Usuario $usuario): JsonResponse|Response
    {
        /** @var Usuario $autor */
        $autor = $request->user();

        if ($usuario->id === $autor->id) {
            return response()->json([
                'message' => 'Não é permitido remover o próprio usuário.',
            ], 403);
        }

        if ($usuario->incendios()->exists()) {
            return response()->json([
                'message' => 'Não é possível remover um usuário que possui incêndios registrados.',
            ], 409);
        }

        LogAuditoria::query()->create([
            'usuario_id' => $autor->id,
            'acao' => 'remocao_usuario',
            'entidade_tipo' => 'usuarios',
            'entidade_id' => $usuario->id,
            'dados_json' => null,
        ]);

        $usuario->tokens()->delete();
        $usuario->delete();

        return response()->noContent();
    }

    public function atualizarFuncao(AtualizarFuncaoRequest $request, Usuario $usuario): JsonResponse|UsuarioResource
    {
        /** @var Usuario $autor */
        $autor = $request->user();

        if ($usuario->id === $autor->id) {
            return response()->json([
                'message' => 'Não é permitido alterar a própria função.',
            ], 403);
        }

        $dados = $request->validated();

        $funcaoAnterior = $usuario->funcao->value;
        $usuario->update([
            'funcao' => $dados['funcao'],
        ]);

        LogAuditoria::query()->create([
            'usuario_id' => $autor->id,
            'acao' => 'atualizacao_funcao_usuario',
            'entidade_tipo' => 'usuarios',
            'entidade_id' => $usuario->id,
            'dados_json' => [
                'funcao_anterior' => $funcaoAnterior,
                'funcao_nova' => $dados['funcao'],
            ],
        ]);

        return new UsuarioResource($usuario->fresh());
    }

    public function atualizarBrigada(AtualizarBrigadaRequest $request, Usuario $usuario): UsuarioResource
    {
        $dados = $request->validated();

        $usuario->update([
            'brigada_id' => $dados['brigada_id'] ?? null,
        ]);

        /** @var Usuario $autor */
        $autor = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $autor->id,
            'acao' => 'atualizacao_brigada_usuario',
            'entidade_tipo' => 'usuarios',
            'entidade_id' => $usuario->id,
            'dados_json' => null,
        ]);

        return new UsuarioResource($usuario->fresh());
    }
}
