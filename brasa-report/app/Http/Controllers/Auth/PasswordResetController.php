<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EsqueciSenhaRequest;
use App\Http\Requests\Auth\RedefinirSenhaRequest;
use App\Mail\RecuperacaoSenhaMail;
use App\Models\LogAuditoria;
use App\Models\TokenRecuperacaoSenha;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    private const MENSAGEM_ESQUECI_GENERICA = 'Se o email estiver cadastrado, você receberá instruções para redefinir a senha.';

    public function enviarToken(EsqueciSenhaRequest $request): JsonResponse
    {
        $email = $request->validated('email');

        $usuario = Usuario::query()->where('email', $email)->first();

        if ($usuario === null) {
            return response()->json([
                'message' => self::MENSAGEM_ESQUECI_GENERICA,
            ]);
        }

        TokenRecuperacaoSenha::query()
            ->where('usuario_id', $usuario->id)
            ->update(['usado' => true]);

        $tokenPlano = Str::random(64);

        TokenRecuperacaoSenha::query()->create([
            'usuario_id' => $usuario->id,
            'token' => $tokenPlano,
            'expira_em' => now()->addMinutes(30),
            'usado' => false,
        ]);

        Mail::to($usuario->email)->queue(new RecuperacaoSenhaMail($usuario, $tokenPlano));

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'solicitacao_reset_senha',
            'entidade_tipo' => 'usuarios',
            'entidade_id' => $usuario->id,
            'dados_json' => null,
        ]);

        return response()->json([
            'message' => self::MENSAGEM_ESQUECI_GENERICA,
        ]);
    }

    public function redefinir(RedefinirSenhaRequest $request): JsonResponse
    {
        $dados = $request->validated();

        $registroToken = TokenRecuperacaoSenha::query()
            ->valido()
            ->where('token', $dados['token'])
            ->first();

        if ($registroToken === null) {
            return response()->json([
                'message' => 'Token de recuperação inválido ou expirado.',
            ], 422);
        }

        $usuario = $registroToken->usuario;

        if ($usuario->email !== $dados['email']) {
            return response()->json([
                'message' => 'O email informado não corresponde a este token.',
            ], 422);
        }

        $usuario->update([
            'senha_hash' => $dados['senha'],
        ]);

        $registroToken->update(['usado' => true]);

        $usuario->tokens()->delete();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'reset_senha_concluido',
            'entidade_tipo' => 'usuarios',
            'entidade_id' => $usuario->id,
            'dados_json' => null,
        ]);

        return response()->json([
            'message' => 'Senha redefinida com sucesso.',
        ]);
    }
}
