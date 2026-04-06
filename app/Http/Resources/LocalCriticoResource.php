<?php

namespace App\Http\Resources;

use App\Models\LocalCritico;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LocalCritico
 */
class LocalCriticoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'tipo' => $this->tipo->value,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'descricao' => $this->descricao,
        ];
    }
}
