<?php

namespace App\Models;

use App\Enums\NivelRiscoIncendio;
use App\Enums\StatusIncendio;
use Database\Factories\IncendioFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incendio extends Model
{
    /** @use HasFactory<IncendioFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'incendios';

    public $timestamps = false;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'latitude',
        'longitude',
        'detectado_em',
        'nivel_risco',
        'status',
        'usuario_id',
        'area_id',
        'local_critico_id',
        'deteccao_satelite_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'detectado_em' => 'datetime',
            'nivel_risco' => NivelRiscoIncendio::class,
            'status' => StatusIncendio::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * @return BelongsTo<AreaMonitorada, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(AreaMonitorada::class, 'area_id');
    }

    /**
     * @return BelongsTo<AreaMonitorada, $this>
     */
    public function areaMonitorada(): BelongsTo
    {
        return $this->area();
    }

    /**
     * @return BelongsTo<LocalCritico, $this>
     */
    public function localCritico(): BelongsTo
    {
        return $this->belongsTo(LocalCritico::class, 'local_critico_id');
    }

    /**
     * @return BelongsTo<DeteccaoSatelite, $this>
     */
    public function deteccaoSatelite(): BelongsTo
    {
        return $this->belongsTo(DeteccaoSatelite::class, 'deteccao_satelite_id');
    }

    /**
     * @return HasMany<LeituraMeteorologica, $this>
     */
    public function leiturasMeteorologicas(): HasMany
    {
        return $this->hasMany(LeituraMeteorologica::class, 'incendio_id');
    }

    /**
     * @return HasMany<DespachoBrigada, $this>
     */
    public function despachosBrigada(): HasMany
    {
        return $this->hasMany(DespachoBrigada::class, 'incendio_id');
    }

    /**
     * @return MorphMany<Alerta, $this>
     */
    public function alertas(): MorphMany
    {
        return $this->morphMany(Alerta::class, 'origem', 'origem_tabela', 'origem_id');
    }
}
