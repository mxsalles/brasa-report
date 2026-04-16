<?php

namespace App\Http\Resources;

use App\Models\LogAuditoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LogAuditoria
 */
class LogAuditoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'acao' => $this->acao,
            'entidade_tipo' => $this->entidade_tipo,
            'entidade_id' => $this->entidade_id,
            'dados_json' => $this->dados_json,
            'criado_em' => $this->criado_em,
            'usuario' => $this->whenLoaded('usuario', fn () => new UsuarioResource($this->usuario)),
        ];
    }
}
