<?php

namespace App\Models;

use App\Enums\TipoAlerta;
use Database\Factories\AlertaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Alerta extends Model
{
    /** @use HasFactory<AlertaFactory> */
    use HasFactory, HasUuids;

    protected $table = 'alertas';

    public $timestamps = false;

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tipo',
        'mensagem',
        'origem_id',
        'origem_tabela',
        'enviado_em',
        'entregue',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoAlerta::class,
            'enviado_em' => 'datetime',
            'entregue' => 'boolean',
        ];
    }

    /**
     * @return MorphTo<Incendio|DeteccaoSatelite|LeituraMeteorologica, $this>
     */
    public function origem(): MorphTo
    {
        return $this->morphTo('origem', 'origem_tabela', 'origem_id');
    }

    /**
     * Relações aninhadas para `loadMorph('origem', ...)`.
     *
     * @return array<class-string, list<string>>
     */
    public static function origemMorphWith(): array
    {
        return [
            Incendio::class => ['area', 'localCritico'],
            LeituraMeteorologica::class => ['incendio.area'],
            DeteccaoSatelite::class => [],
        ];
    }
}
