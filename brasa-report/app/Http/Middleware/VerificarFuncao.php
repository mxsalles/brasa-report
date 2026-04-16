<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarFuncao
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Não autenticado.',
            ], 401);
        }

        $permitidas = array_values(array_filter(array_map('trim', explode('|', $roles))));

        if (! in_array($user->funcao->value, $permitidas, true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Acesso negado.',
                ], 403);
            }

            abort(403, 'Acesso negado.');
        }

        return $next($request);
    }
}
