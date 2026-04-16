<?php

namespace App\Http\Resources;

use App\Models\Alerta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Alerta
 */
class AlertaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo->value,
            'mensagem' => $this->mensagem,
            'origem_id' => $this->origem_id,
            'origem_tabela' => $this->origem_tabela,
            'enviado_em' => $this->enviado_em,
            'entregue' => $this->entregue,
        ];
    }
}
