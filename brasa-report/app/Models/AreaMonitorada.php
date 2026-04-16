<?php

namespace App\Models;

use Database\Factories\AreaMonitoradaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AreaMonitorada extends Model
{
    /** @use HasFactory<AreaMonitoradaFactory> */
    use HasFactory, HasUuids;

    protected $table = 'areas_monitoradas';

    public $timestamps = false;

    protected $keyType = 'string';

    public $incrementing = false;

    /** Extensões aceitas para upload (referência para validação / UI). */
    public const FORMATOS_ACEITOS = ['geojson', 'json', 'kml', 'shp', 'zip'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'caminho_geopackage',
        'geometria_geojson',
        'importado_em',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'importado_em' => 'datetime',
            'geometria_geojson' => 'array',
        ];
    }

    /**
     * @return HasMany<Incendio, $this>
     */
    public function incendios(): HasMany
    {
        return $this->hasMany(Incendio::class, 'area_id');
    }
}
