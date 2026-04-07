<?php

namespace App\Http\Resources;

use App\Models\LeituraMeteorologica;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeituraMeteorologica
 */
class LeituraMeteorologicaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'incendio_id' => $this->incendio_id,
            'temperatura' => $this->temperatura,
            'umidade' => $this->umidade,
            'velocidade_vento' => $this->velocidade_vento,
            'registrado_em' => $this->registrado_em,
            'gera_alerta' => $this->gera_alerta,
        ];
    }
}
