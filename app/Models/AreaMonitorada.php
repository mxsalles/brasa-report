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

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'caminho_geopackage',
        'geometria_wkt',
        'importado_em',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'importado_em' => 'datetime',
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
