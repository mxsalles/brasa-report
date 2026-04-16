<?php

namespace App\Http\Resources;

use App\Models\Incendio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Incendio
 */
class IncendioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'detectado_em' => $this->detectado_em,
            'nivel_risco' => $this->nivel_risco->value,
            'status' => $this->status->value,
            'usuario_id' => $this->usuario_id,
            'area_id' => $this->area_id,
            'local_critico_id' => $this->local_critico_id,
            'deteccao_satelite_id' => $this->deteccao_satelite_id,
            'area' => $this->whenLoaded('area', fn () => new AreaMonitoradaResource($this->area)),
            'local_critico' => $this->whenLoaded('localCritico', fn () => new LocalCriticoResource($this->localCritico)),
            'deteccao_satelite' => $this->whenLoaded('deteccaoSatelite', fn () => new DeteccaoSateliteResource($this->deteccaoSatelite)),
            'usuario' => $this->whenLoaded('usuario', fn () => new UsuarioResource($this->usuario)),
        ];
    }
}
