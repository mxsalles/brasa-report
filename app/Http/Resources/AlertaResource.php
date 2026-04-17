<?php

namespace App\Http\Resources;

use App\Models\Alerta;
use App\Models\DeteccaoSatelite;
use App\Models\Incendio;
use App\Models\LeituraMeteorologica;
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
            'origem_label' => self::origemTabelaLabel($this->origem_tabela),
            'origem_resumo' => $this->when(
                $this->relationLoaded('origem'),
                fn (): ?array => $this->mapOrigemResumo(),
            ),
            'enviado_em' => $this->enviado_em,
            'entregue' => $this->entregue,
        ];
    }

    public static function origemTabelaLabel(?string $origemTabela): string
    {
        return match ($origemTabela) {
            'incendios' => 'Ocorrência de incêndio',
            'leituras_meteorologicas' => 'Leitura meteorológica',
            'deteccoes_satelite' => 'Detecção por satélite',
            default => 'Origem do sistema',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapOrigemResumo(): ?array
    {
        $origem = $this->origem;

        if ($origem instanceof Incendio) {
            return [
                'tipo' => 'incendio',
                'incendio_id' => $origem->id,
                'area_nome' => $origem->relationLoaded('area') && $origem->area
                    ? $origem->area->nome
                    : null,
                'detectado_em' => $origem->detectado_em?->toIso8601String(),
                'status' => $origem->status->value,
                'local_critico_nome' => $origem->relationLoaded('localCritico')
                    ? $origem->localCritico?->nome
                    : null,
            ];
        }

        if ($origem instanceof LeituraMeteorologica) {
            $incendio = $origem->relationLoaded('incendio') ? $origem->incendio : null;
            $areaNome = null;
            if ($incendio !== null && $incendio->relationLoaded('area') && $incendio->area) {
                $areaNome = $incendio->area->nome;
            }

            return [
                'tipo' => 'leitura_meteorologica',
                'leitura_id' => $origem->id,
                'incendio_id' => $origem->incendio_id,
                'area_nome' => $areaNome,
                'temperatura' => (string) $origem->temperatura,
                'umidade' => (string) $origem->umidade,
                'registrado_em' => $origem->registrado_em?->toIso8601String(),
            ];
        }

        if ($origem instanceof DeteccaoSatelite) {
            return [
                'tipo' => 'deteccao_satelite',
                'deteccao_id' => $origem->id,
                'fonte' => $origem->fonte,
                'latitude' => (string) $origem->latitude,
                'longitude' => (string) $origem->longitude,
                'confianca' => (string) $origem->confianca,
                'detectado_em' => $origem->detectado_em?->toIso8601String(),
            ];
        }

        return null;
    }
}
