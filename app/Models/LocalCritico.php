<?php

namespace App\Models;

use App\Enums\TipoLocalCritico;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalCritico extends Model
{
    protected $table = 'locais_criticos';

    public $timestamps = false;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'tipo',
        'latitude',
        'longitude',
        'descricao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoLocalCritico::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * @return HasMany<Incendio, $this>
     */
    public function incendios(): HasMany
    {
        return $this->hasMany(Incendio::class, 'local_critico_id');
    }
}
