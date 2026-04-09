<?php

namespace App\Models;

use Database\Factories\LeituraMeteorologicaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LeituraMeteorologica extends Model
{
    /** @use HasFactory<LeituraMeteorologicaFactory> */
    use HasFactory, HasUuids;

    protected $table = 'leituras_meteorologicas';

    public $timestamps = false;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'incendio_id',
        'temperatura',
        'umidade',
        'velocidade_vento',
        'registrado_em',
        'gera_alerta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registrado_em' => 'datetime',
            'gera_alerta' => 'boolean',
            'temperatura' => 'decimal:2',
            'umidade' => 'decimal:2',
            'velocidade_vento' => 'decimal:2',
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
     * @return MorphMany<Alerta, $this>
     */
    public function alertas(): MorphMany
    {
        return $this->morphMany(Alerta::class, 'origem', 'origem_tabela', 'origem_id');
    }
}
