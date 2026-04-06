<?php

namespace App\Http\Resources;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Usuario
 */
class UsuarioResource extends JsonResource
{
    public function __construct(
        $resource,
        protected bool $somenteMembroBrigada = false,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->somenteMembroBrigada) {
            return [
                'id' => $this->id,
                'nome' => $this->nome,
                'funcao' => $this->funcao->value,
            ];
        }

        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'email' => $this->email,
            'funcao' => $this->funcao->value,
            'brigada_id' => $this->brigada_id,
            'criado_em' => $this->criado_em,
        ];
    }
}
