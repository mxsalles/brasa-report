<?php

namespace App\Models;

use Carbon\CarbonInterval;
use Database\Factories\DespachoBrigadaFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DespachoBrigada extends Model
{
    /** @use HasFactory<DespachoBrigadaFactory> */
    use HasFactory;

    protected $table = 'despachos_brigada';

    public $timestamps = false;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'incendio_id',
        'brigada_id',
        'despachado_em',
        'chegada_em',
        'finalizado_em',
        'observacoes',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'tempo_resposta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'despachado_em' => 'datetime',
            'chegada_em' => 'datetime',
            'finalizado_em' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Incendio, $this>
     */
    public function incendio(): BelongsTo
    {
        return $this->belongsTo(Incendio::class, 'incendio_id');
    }

    /**
     * @return BelongsTo<Brigada, $this>
     */
    public function brigada(): BelongsTo
    {
        return $this->belongsTo(Brigada::class, 'brigada_id');
    }

    /**
     * Diferença entre chegada e despacho quando ambos existem.
     *
     * @return Attribute<CarbonInterval|null, never>
     */
    protected function tempoResposta(): Attribute
    {
        return Attribute::get(function (): ?CarbonInterval {
            if ($this->chegada_em === null || $this->despachado_em === null) {
                return null;
            }

            return $this->despachado_em->diffAsCarbonInterval($this->chegada_em);
        });
    }
}
