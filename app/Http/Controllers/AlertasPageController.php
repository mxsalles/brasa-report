<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlertaResource;
use App\Models\Alerta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertasPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $paginator = Alerta::query()
            ->orderByDesc('enviado_em')
            ->paginate(20);

        $paginator->getCollection()->loadMorph('origem', Alerta::origemMorphWith());

        return Inertia::render('alertas', [
            'alertas' => [
                'data' => AlertaResource::collection($paginator->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
        ]);
    }
}
