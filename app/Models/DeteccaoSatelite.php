<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeteccaoSatelite extends Model
{
    protected $table = 'deteccoes_satelite';

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
        'confianca',
        'fonte',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'detectado_em' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'confianca' => 'decimal:2',
        ];
    }

    /**
     * @return HasOne<Incendio, $this>
     */
    public function incendio(): HasOne
    {
        return $this->hasOne(Incendio::class, 'deteccao_satelite_id');
    }
}
