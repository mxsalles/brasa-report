<?php

namespace App\Enums;

enum StatusIncendio: string
{
    case Ativo = 'ativo';
    case EmCombate = 'em_combate';
    case Contido = 'contido';
    case Resolvido = 'resolvido';

    public function proximo(): ?self
    {
        return match ($this) {
            self::Ativo => self::EmCombate,
            self::EmCombate => self::Contido,
            self::Contido => self::Resolvido,
            self::Resolvido => null,
        };
    }
}
