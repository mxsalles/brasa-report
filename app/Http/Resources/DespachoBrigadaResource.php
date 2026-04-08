<?php

namespace App\Http\Resources;

use App\Models\DespachoBrigada;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DespachoBrigada
 */
class DespachoBrigadaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tempoRespostaMinutos = null;

        if ($this->chegada_em !== null && $this->despachado_em !== null) {
            $tempoRespostaMinutos = $this->despachado_em->diffInMinutes($this->chegada_em);
        }

        return [
            'id' => $this->id,
            'incendio_id' => $this->incendio_id,
            'brigada_id' => $this->brigada_id,
            'despachado_em' => $this->despachado_em,
            'chegada_em' => $this->chegada_em,
            'finalizado_em' => $this->finalizado_em,
            'observacoes' => $this->observacoes,
            'brigada' => $this->whenLoaded('brigada', fn () => new BrigadaResource($this->brigada)),
            'tempo_resposta_minutos' => $tempoRespostaMinutos,
        ];
    }
}
