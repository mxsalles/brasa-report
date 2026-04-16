<?php

namespace App\Enums;

enum StatusIncendio: string
{
    case Ativo = 'ativo';
    case Contido = 'contido';
    case Resolvido = 'resolvido';
}
