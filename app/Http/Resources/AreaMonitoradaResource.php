<?php

namespace App\Http\Resources;

use App\Models\AreaMonitorada;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AreaMonitorada
 */
class AreaMonitoradaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'caminho_geopackage' => $this->caminho_geopackage,
            'geometria_geojson' => $this->geometria_geojson,
            'importado_em' => $this->importado_em,
        ];
    }
}
