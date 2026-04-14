<?php

namespace App\Http\Controllers;

use App\Models\AreaMonitorada;
use Inertia\Inertia;
use Inertia\Response;

class RegistrarIncendioController extends Controller
{
    public function __invoke(): Response
    {
        $area = AreaMonitorada::query()
            ->where('nome', 'Pantanal Geral')
            ->first();

        return Inertia::render('registrar-incendio', [
            'areaPadrao' => $area !== null
                ? ['id' => (string) $area->getKey()]
                : null,
        ]);
    }
}
