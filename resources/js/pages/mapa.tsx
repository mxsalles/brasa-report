import { Head, router, usePage } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import {
    CloudRain,
    Droplets,
    Info,
    Layers,
    List,
    ScrollText,
    Thermometer,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import type { MapIncendio } from '@/components/map-component';
import { MapComponent } from '@/components/map-component';
import { RiskBadge } from '@/components/risk-badge';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios-setup';
import { cn } from '@/lib/utils';
import { mapa as mapaRoute } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type {
    ClimaDashboard,
    NivelRisco,
    StatusIncendio,
} from '@/types/dashboard';

type HistoricoEvento = {
    em: string | null;
    tipo: string;
    rotulo: string;
    detalhe: string | null;
    usuario_nome?: string | null;
    brigada_nome?: string | null;
};

type HistoricoPayload = {
    registro: {
        detectado_em: string | null;
        registrado_por: string | null;
        area_nome: string;
    };
    metricas: {
        primeira_chegada_em: string | null;
        horas_brigadas_no_local: number;
        horas_em_combate: number | null;
    };
    eventos: HistoricoEvento[];
};

type PageProps = {
    incendios: MapIncendio[];
    condicoesClimaticas: ClimaDashboard | null;
    podeGerenciar: boolean;
};

const statusFlowLabels: Partial<Record<StatusIncendio, string>> = {
    ativo: 'Marcar como Em Combate',
    em_combate: 'Marcar como Contido',
    contido: 'Marcar como Resolvido',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mapa', href: mapaRoute().url },
];

function tipoEventoLabel(tipo: string): string {
    const map: Record<string, string> = {
        registro: 'Registro',
        mudanca_status: 'Status',
        mudanca_risco: 'Risco',
        atualizacao: 'Atualização',
        despacho: 'Despacho',
        chegada: 'Chegada',
        finalizacao_despacho: 'Despacho',
    };

    return map[tipo] ?? tipo;
}

export default function Mapa() {
    const { incendios, condicoesClimaticas, podeGerenciar } =
        usePage<PageProps>().props;

    const [painelAberto, setPainelAberto] = useState(false);

    const [incendioModalOpen, setIncendioModalOpen] = useState(false);
    const [modalAba, setModalAba] = useState<'detalhes' | 'historico'>(
        'detalhes',
    );
    const [selectedIncendio, setSelectedIncendio] =
        useState<MapIncendio | null>(null);
    const [statusLoading, setStatusLoading] = useState(false);
    const [historicoData, setHistoricoData] = useState<HistoricoPayload | null>(
        null,
    );
    const [historicoLoading, setHistoricoLoading] = useState(false);

    const openIncendioModal = useCallback(
        (incendioId: string) => {
            const inc = incendios.find((i) => i.id === incendioId);
            if (inc) {
                setSelectedIncendio(inc);
                setModalAba('detalhes');
                setHistoricoData(null);
                setIncendioModalOpen(true);
            }
        },
        [incendios],
    );

    useEffect(() => {
        if (!incendioModalOpen || !selectedIncendio || modalAba !== 'historico') {
            return;
        }

        let cancelled = false;
        setHistoricoLoading(true);
        axios
            .get<HistoricoPayload>(
                `/api/incendios/${selectedIncendio.id}/historico`,
            )
            .then(({ data }) => {
                if (!cancelled) {
                    setHistoricoData(data);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    toast.error('Não foi possível carregar o histórico');
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setHistoricoLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [incendioModalOpen, selectedIncendio?.id, modalAba]);

    const avancarStatus = useCallback(async () => {
        if (!selectedIncendio) {
            return;
        }

        const next = statusFlowLabels[selectedIncendio.status];
        if (!next) {
            return;
        }

        const statusMap: Record<string, StatusIncendio> = {
            ativo: 'em_combate',
            em_combate: 'contido',
            contido: 'resolvido',
        };

        const novoStatus = statusMap[selectedIncendio.status];
        if (!novoStatus) {
            return;
        }

        setStatusLoading(true);
        try {
            await axios.patch(
                `/api/incendios/${selectedIncendio.id}/status`,
                { status: novoStatus },
            );
            toast.success('Status atualizado com sucesso');
            setIncendioModalOpen(false);
            router.reload();
        } catch {
            toast.error('Erro ao atualizar status');
        } finally {
            setStatusLoading(false);
        }
    }, [selectedIncendio]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mapa" />
            <div className="relative isolate h-[calc(100dvh-5rem)] min-h-[420px] w-full overflow-hidden rounded-xl border border-border/60">
                <MapComponent
                    incendios={incendios}
                    className="absolute inset-0"
                    onMarkerClick={openIncendioModal}
                />

                <div className="absolute top-4 left-4 z-1000">
                    <Button
                        type="button"
                        size="sm"
                        onClick={() => setPainelAberto(!painelAberto)}
                        className="gap-2 shadow-lg"
                    >
                        {painelAberto ? (
                            <X className="size-4" />
                        ) : (
                            <List className="size-4" />
                        )}
                        {painelAberto ? 'Fechar' : 'Ocorrências'}
                    </Button>
                </div>

                <div className="absolute top-4 right-14 z-1000 max-w-[220px] rounded-lg border border-border bg-card/95 p-3 shadow-md backdrop-blur-sm">
                    <p className="mb-2 text-xs font-semibold text-foreground">
                        Condições Climáticas
                    </p>
                    {condicoesClimaticas ? (
                        <div className="space-y-1.5">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-1.5">
                                    <Thermometer className="size-3 text-critical" />
                                    <span className="text-xs text-muted-foreground">
                                        Temp.
                                    </span>
                                </div>
                                <span className="text-xs font-bold text-critical">
                                    {Math.round(condicoesClimaticas.temperatura_c)}
                                    °C
                                </span>
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-1.5">
                                    <Droplets className="size-3 text-primary" />
                                    <span className="text-xs text-muted-foreground">
                                        Umidade
                                    </span>
                                </div>
                                <span className="text-xs font-bold text-warning">
                                    {condicoesClimaticas.umidade_pct}%
                                </span>
                            </div>
                            <div className="border-t border-border pt-1.5">
                                <div className="flex items-center gap-1.5">
                                    <CloudRain className="size-3 text-muted-foreground" />
                                    <span className="text-[10px] text-muted-foreground">
                                        Atualizado:{' '}
                                        {new Date(
                                            condicoesClimaticas.atualizado_em,
                                        ).toLocaleString('pt-BR')}
                                    </span>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <p className="text-xs text-muted-foreground">
                            Sem dados disponíveis
                        </p>
                    )}
                </div>

                <div className="absolute right-4 bottom-4 z-1000 rounded-lg border border-border bg-card/95 p-3 shadow-md backdrop-blur-sm">
                    <div className="mb-2 flex items-center gap-2">
                        <Layers className="size-3 text-muted-foreground" />
                        <span className="text-xs font-medium text-muted-foreground">
                            Legenda
                        </span>
                    </div>
                    <div className="space-y-1.5">
                        <div className="flex items-center gap-2">
                            <span className="size-3 rounded-full bg-critical" />
                            <span className="text-xs">Ativo</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="size-3 rounded-full bg-[#f97316]" />
                            <span className="text-xs">Em Combate</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="size-3 rounded-full bg-warning" />
                            <span className="text-xs">Contido</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="size-3 rounded-full bg-resolved" />
                            <span className="text-xs">Resolvido</span>
                        </div>
                    </div>
                </div>

                <AnimatePresence>
                    {painelAberto ? (
                        <motion.div
                            key="panel"
                            initial={{ x: -300, opacity: 0 }}
                            animate={{ x: 0, opacity: 1 }}
                            exit={{ x: -300, opacity: 0 }}
                            className="absolute top-14 left-4 z-1000 max-h-[calc(100%-7rem)] w-80 overflow-auto rounded-xl border border-border bg-card/95 shadow-lg backdrop-blur-sm"
                        >
                            <div className="space-y-3 p-4">
                                <h3 className="text-sm font-semibold">
                                    Ocorrências ({incendios.length})
                                </h3>
                                {incendios.length === 0 ? (
                                    <p className="text-xs text-muted-foreground">
                                        Nenhuma ocorrência ativa.
                                    </p>
                                ) : (
                                    incendios.map((inc) => (
                                        <div
                                            key={inc.id}
                                            className="cursor-pointer rounded-lg bg-secondary/50 p-3 transition-colors hover:bg-secondary/80"
                                            onClick={() =>
                                                openIncendioModal(inc.id)
                                            }
                                            role="button"
                                            tabIndex={0}
                                            onKeyDown={(e) => {
                                                if (
                                                    e.key === 'Enter' ||
                                                    e.key === ' '
                                                ) {
                                                    openIncendioModal(inc.id);
                                                }
                                            }}
                                        >
                                            <div className="mb-1 flex flex-wrap items-center gap-2">
                                                <span className="text-sm font-medium">
                                                    {inc.area_nome}
                                                </span>
                                                <StatusBadge
                                                    status={inc.status}
                                                />
                                            </div>
                                            {inc.local_critico_nome && (
                                                <p className="text-xs text-muted-foreground">
                                                    📍{' '}
                                                    {inc.local_critico_nome}
                                                </p>
                                            )}
                                            <div className="mt-2 flex items-center justify-between">
                                                <RiskBadge
                                                    nivel={
                                                        inc.nivel_risco as NivelRisco
                                                    }
                                                />
                                                {inc.detectado_em && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {new Date(
                                                            inc.detectado_em,
                                                        ).toLocaleDateString(
                                                            'pt-BR',
                                                        )}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </motion.div>
                    ) : null}
                </AnimatePresence>
            </div>

            <Dialog
                open={incendioModalOpen}
                onOpenChange={(open) => {
                    if (!statusLoading) {
                        setIncendioModalOpen(open);
                        if (!open) {
                            setModalAba('detalhes');
                            setHistoricoData(null);
                        }
                    }
                }}
            >
                <DialogContent className="max-h-[min(90dvh,720px)] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {selectedIncendio?.area_nome ?? 'Incêndio'}
                        </DialogTitle>
                        <DialogDescription>
                            {podeGerenciar
                                ? 'Detalhes e histórico da ocorrência'
                                : 'Informações da ocorrência'}
                        </DialogDescription>
                    </DialogHeader>

                    {selectedIncendio && (
                        <div className="space-y-4">
                            <div className="grid w-full max-w-md grid-cols-2 gap-2 rounded-lg border border-border bg-muted/40 p-1">
                                <button
                                    type="button"
                                    onClick={() => setModalAba('detalhes')}
                                    className={cn(
                                        'flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                        modalAba === 'detalhes'
                                            ? 'bg-background text-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    <Info className="size-4" />
                                    Detalhes
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setModalAba('historico')}
                                    className={cn(
                                        'flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                        modalAba === 'historico'
                                            ? 'bg-background text-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    <ScrollText className="size-4" />
                                    Histórico
                                </button>
                            </div>

                            {modalAba === 'detalhes' ? (
                                <>
                                    <div className="grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <span className="text-xs text-muted-foreground">
                                                Status
                                            </span>
                                            <div className="mt-1">
                                                <StatusBadge
                                                    status={
                                                        selectedIncendio.status
                                                    }
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <span className="text-xs text-muted-foreground">
                                                Nível de Risco
                                            </span>
                                            <div className="mt-1">
                                                <RiskBadge
                                                    nivel={
                                                        selectedIncendio.nivel_risco as NivelRisco
                                                    }
                                                />
                                            </div>
                                        </div>
                                        {selectedIncendio.detectado_em && (
                                            <div className="col-span-2">
                                                <span className="text-xs text-muted-foreground">
                                                    Detectado em
                                                </span>
                                                <p className="mt-0.5 text-sm font-medium">
                                                    {new Date(
                                                        selectedIncendio.detectado_em,
                                                    ).toLocaleString('pt-BR')}
                                                </p>
                                            </div>
                                        )}
                                        {selectedIncendio.local_critico_nome && (
                                            <div className="col-span-2">
                                                <span className="text-xs text-muted-foreground">
                                                    Local crítico
                                                </span>
                                                <p className="mt-0.5 text-sm font-medium">
                                                    {
                                                        selectedIncendio.local_critico_nome
                                                    }
                                                </p>
                                            </div>
                                        )}
                                        <div className="col-span-2">
                                            <span className="text-xs text-muted-foreground">
                                                Coordenadas
                                            </span>
                                            <p className="mt-0.5 font-mono text-xs">
                                                {selectedIncendio.latitude.toFixed(
                                                    5,
                                                )}
                                                ,{' '}
                                                {selectedIncendio.longitude.toFixed(
                                                    5,
                                                )}
                                            </p>
                                        </div>
                                    </div>

                                    {podeGerenciar &&
                                        statusFlowLabels[
                                            selectedIncendio.status
                                        ] && (
                                            <DialogFooter>
                                                <Button
                                                    onClick={avancarStatus}
                                                    disabled={statusLoading}
                                                    className="w-full"
                                                >
                                                    {statusLoading
                                                        ? 'Atualizando...'
                                                        : statusFlowLabels[
                                                              selectedIncendio
                                                                  .status
                                                          ]}
                                                </Button>
                                            </DialogFooter>
                                        )}

                                    {selectedIncendio.status === 'resolvido' && (
                                        <div className="rounded-lg border border-resolved/30 bg-resolved/10 p-3 text-center text-sm text-resolved">
                                            Este incêndio já foi resolvido.
                                        </div>
                                    )}
                                </>
                            ) : (
                                <div className="space-y-4">
                                    {historicoLoading && (
                                        <p className="text-sm text-muted-foreground">
                                            Carregando histórico…
                                        </p>
                                    )}
                                    {!historicoLoading && historicoData && (
                                        <>
                                            <div className="rounded-lg border border-border bg-muted/30 p-3 text-sm">
                                                <p className="text-xs font-semibold text-muted-foreground">
                                                    Registro
                                                </p>
                                                <p className="mt-1">
                                                    <span className="text-muted-foreground">
                                                        Por{' '}
                                                    </span>
                                                    {historicoData.registro
                                                        .registrado_por ??
                                                        '—'}
                                                </p>
                                                {historicoData.registro
                                                    .detectado_em && (
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {new Date(
                                                            historicoData.registro.detectado_em,
                                                        ).toLocaleString('pt-BR')}
                                                    </p>
                                                )}
                                            </div>

                                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                                <div className="rounded-lg border border-border p-2 text-center">
                                                    <p className="text-[10px] font-medium text-muted-foreground">
                                                        Horas em combate
                                                        (status)
                                                    </p>
                                                    <p className="text-lg font-bold tabular-nums">
                                                        {historicoData.metricas
                                                            .horas_em_combate !=
                                                        null
                                                            ? historicoData.metricas.horas_em_combate.toFixed(
                                                                  2,
                                                              )
                                                            : '—'}
                                                    </p>
                                                </div>
                                                <div className="rounded-lg border border-border p-2 text-center">
                                                    <p className="text-[10px] font-medium text-muted-foreground">
                                                        Horas brigadas no
                                                        local
                                                    </p>
                                                    <p className="text-lg font-bold tabular-nums">
                                                        {historicoData.metricas.horas_brigadas_no_local.toFixed(
                                                            1,
                                                        )}
                                                    </p>
                                                </div>
                                                <div className="rounded-lg border border-border p-2 text-center">
                                                    <p className="text-[10px] font-medium text-muted-foreground">
                                                        1ª chegada
                                                    </p>
                                                    <p className="text-xs font-medium">
                                                        {historicoData.metricas
                                                            .primeira_chegada_em
                                                            ? new Date(
                                                                  historicoData.metricas.primeira_chegada_em,
                                                              ).toLocaleString(
                                                                  'pt-BR',
                                                              )
                                                            : '—'}
                                                    </p>
                                                </div>
                                            </div>

                                            <div>
                                                <p className="mb-2 text-xs font-semibold text-muted-foreground">
                                                    Linha do tempo
                                                </p>
                                                <ul className="relative space-y-3 border-l-2 border-border pl-4">
                                                    {historicoData.eventos.map(
                                                        (ev, idx) => (
                                                            <li
                                                                key={`${ev.em}-${idx}`}
                                                                className="relative"
                                                            >
                                                                <span className="absolute -left-[21px] top-1.5 size-2 rounded-full bg-primary" />
                                                                <p className="text-xs text-muted-foreground">
                                                                    {ev.em
                                                                        ? new Date(
                                                                              ev.em,
                                                                          ).toLocaleString(
                                                                              'pt-BR',
                                                                          )
                                                                        : '—'}
                                                                </p>
                                                                <p className="text-sm font-medium">
                                                                    {ev.rotulo}
                                                                </p>
                                                                <span className="text-[10px] uppercase text-muted-foreground">
                                                                    {tipoEventoLabel(
                                                                        ev.tipo,
                                                                    )}
                                                                </span>
                                                                {ev.detalhe && (
                                                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                                                        {
                                                                            ev.detalhe
                                                                        }
                                                                    </p>
                                                                )}
                                                                {ev.usuario_nome && (
                                                                    <p className="text-xs text-muted-foreground">
                                                                        Por{' '}
                                                                        {
                                                                            ev.usuario_nome
                                                                        }
                                                                    </p>
                                                                )}
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                            </div>
                                        </>
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
