<?php

namespace App\Http\Resources;

use App\Models\DeteccaoSatelite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeteccaoSatelite
 */
class DeteccaoSateliteResource extends JsonResource
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
            'confianca' => $this->confianca,
            'fonte' => $this->fonte,
        ];
    }
}
