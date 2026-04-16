<?php

namespace App\Enums;

enum TipoAlerta: string
{
    case TemperaturaAlta = 'temperatura_alta';
    case UmidadeBaixa = 'umidade_baixa';
    case FogoDetectado = 'fogo_detectado';
    case ProximidadeLocalCritico = 'proximidade_local_critico';
}
