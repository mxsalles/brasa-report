<?php

namespace App\Enums;

enum FuncaoUsuario: string
{
    case Brigadista = 'brigadista';
    case Gestor = 'gestor';
    case Admin = 'admin';
}
