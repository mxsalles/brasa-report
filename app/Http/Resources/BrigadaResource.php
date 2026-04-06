<?php

namespace App\Http\Resources;

use App\Models\Brigada;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Brigada
 */
class BrigadaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'latitude_atual' => $this->latitude_atual,
            'longitude_atual' => $this->longitude_atual,
            'disponivel' => $this->disponivel,
            'usuarios_count' => $this->when(isset($this->usuarios_count), $this->usuarios_count),
            'membros' => $this->whenLoaded('usuarios', function () {
                return $this->usuarios->map(function ($usuario) {
                    return (new UsuarioResource($usuario, true))->resolve();
                });
            }),
        ];
    }
}
