<?php

namespace App\Http\Controllers;

use App\Enums\FuncaoUsuario;
use App\Enums\StatusIncendio;
use App\Http\Resources\BrigadaResource;
use App\Models\Brigada;
use App\Models\DespachoBrigada;
use App\Models\Incendio;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrigadasPageController extends Controller
{
    public function index(Request $request): Response
    {
        $brigadas = Brigada::query()
            ->withCount('usuarios')
            ->orderBy('nome')
            ->get()
            ->map(fn (Brigada $brigada): array => (new BrigadaResource($brigada))->resolve());

        $mapDespacho = fn (DespachoBrigada $d): array => [
            'id' => $d->id,
            'incendio_id' => $d->incendio_id,
            'brigada_nome' => $d->brigada?->nome ?? '—',
            'incendio_area_nome' => $d->incendio?->area?->nome ?? '—',
            'despachado_em' => $d->despachado_em?->toIso8601String(),
            'chegada_em' => $d->chegada_em?->toIso8601String(),
            'finalizado_em' => $d->finalizado_em?->toIso8601String(),
            'observacoes' => $d->observacoes,
        ];

        $despachosAtivos = DespachoBrigada::query()
            ->with(['brigada', 'incendio.area'])
            ->whereNull('finalizado_em')
            ->latest('despachado_em')
            ->get()
            ->map($mapDespacho);

        $despachosFinalizados = DespachoBrigada::query()
            ->with(['brigada', 'incendio.area'])
            ->whereNotNull('finalizado_em')
            ->latest('finalizado_em')
            ->limit(20)
            ->get()
            ->map($mapDespacho);

        /** @var Usuario $auth */
        $auth = $request->user();

        $podeGerenciar = in_array($auth->funcao, [
            FuncaoUsuario::Gestor,
            FuncaoUsuario::Administrador,
        ], true);

        $usuariosDisponiveis = $podeGerenciar
            ? Usuario::query()
                ->whereNull('brigada_id')
                ->where('bloqueado', false)
                ->orderBy('nome')
                ->get()
                ->map(fn (Usuario $u): array => [
                    'id' => $u->id,
                    'nome' => $u->nome,
                    'funcao' => $u->funcao->value,
                ])
            : [];

        $incendiosAtivos = $podeGerenciar
            ? Incendio::query()
                ->with('area')
                ->whereIn('status', [StatusIncendio::Ativo, StatusIncendio::Contido])
                ->latest('detectado_em')
                ->get()
                ->map(fn (Incendio $i): array => [
                    'id' => $i->id,
                    'latitude' => $i->latitude,
                    'longitude' => $i->longitude,
                    'detectado_em' => $i->detectado_em?->toIso8601String(),
                    'nivel_risco' => $i->nivel_risco->value,
                    'status' => $i->status->value,
                    'area_nome' => $i->area?->nome ?? '—',
                ])
            : [];

        return Inertia::render('brigadas', [
            'brigadas' => $brigadas,
            'despachosAtivos' => $despachosAtivos,
            'despachosFinalizados' => $despachosFinalizados,
            'podeGerenciar' => $podeGerenciar,
            'funcaoAutenticado' => $auth->funcao->value,
            'usuariosDisponiveis' => $usuariosDisponiveis,
            'incendiosAtivos' => $incendiosAtivos,
        ]);
    }
}
