<?php

namespace App\Enums;

enum FuncaoUsuario: string
{
    case User = 'user';
    case Brigadista = 'brigadista';
    case Gestor = 'gestor';
    case Administrador = 'administrador';
}
