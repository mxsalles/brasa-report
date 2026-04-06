<?php

namespace App\Models;

use Database\Factories\BrigadaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brigada extends Model
{
    /** @use HasFactory<BrigadaFactory> */
    use HasFactory, HasUuids;

    protected $table = 'brigadas';

    public $timestamps = false;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'tipo',
        'latitude_atual',
        'longitude_atual',
        'disponivel',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'disponivel' => 'boolean',
            'latitude_atual' => 'decimal:8',
            'longitude_atual' => 'decimal:8',
        ];
    }

    /**
     * @return HasMany<Usuario, $this>
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'brigada_id');
    }

    /**
     * @return HasMany<DespachoBrigada, $this>
     */
    public function despachosBrigada(): HasMany
    {
        return $this->hasMany(DespachoBrigada::class, 'brigada_id');
    }
}
