<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\LogAuditoria;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = Usuario::query()
            ->where('email', $request->validated('email'))
            ->first();

        if ($usuario === null || ! Hash::check($request->validated('senha'), $usuario->senha_hash)) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        if ($usuario->bloqueado) {
            return response()->json([
                'message' => 'Conta bloqueada.',
            ], 403);
        }

        /** @var Usuario $usuario */
        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'login',
            'entidade_tipo' => 'usuarios',
            'entidade_id' => $usuario->id,
            'dados_json' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent() ?? '',
            ],
        ]);

        $plainTextToken = $usuario->createToken('brasa')->plainTextToken;

        return response()->json([
            'token' => $plainTextToken,
            'usuario' => (new UsuarioResource($usuario))->resolve(),
        ]);
    }

    public function logout(Request $request): Response
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        LogAuditoria::query()->create([
            'usuario_id' => $usuario->id,
            'acao' => 'logout',
            'entidade_tipo' => 'usuarios',
            'entidade_id' => $usuario->id,
            'dados_json' => null,
        ]);

        $accessToken = $usuario->currentAccessToken();
        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }

        return response()->noContent();
    }

    public function me(Request $request): UsuarioResource
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        return new UsuarioResource($usuario);
    }
}
