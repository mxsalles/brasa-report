import { Head } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import {
    CloudRain,
    Droplets,
    Layers,
    List,
    Thermometer,
    Wind,
    X,
} from 'lucide-react';
import { useState } from 'react';

import { MapComponent } from '@/components/map-component';
import { RiskBadge } from '@/components/risk-badge';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    mockClimaMapa,
    mockIncendiosMapa,
} from '@/data/operacoes-mock';
import AppLayout from '@/layouts/app-layout';
import { mapa as mapaRoute } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mapa', href: mapaRoute().url },
];

export default function Mapa() {
    const [painelAberto, setPainelAberto] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mapa" />
            <div className="relative h-[calc(100dvh-5rem)] min-h-[420px] w-full overflow-hidden rounded-xl border border-border/60">
                <MapComponent
                    incendios={mockIncendiosMapa}
                    className="absolute inset-0"
                />

                <div className="absolute top-4 left-4 z-[1000]">
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

                <div className="absolute top-4 right-14 z-[1000] max-w-[220px] rounded-lg border border-border bg-card/95 p-3 shadow-md backdrop-blur-sm">
                    <p className="mb-2 text-xs font-semibold text-foreground">
                        Condições Climáticas
                    </p>
                    <div className="space-y-1.5">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-1.5">
                                <Thermometer className="size-3 text-critical" />
                                <span className="text-xs text-muted-foreground">
                                    Temp.
                                </span>
                            </div>
                            <span className="text-xs font-bold text-critical">
                                {mockClimaMapa.temperatura}°C
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
                                {mockClimaMapa.umidade}%
                            </span>
                        </div>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-1.5">
                                <Wind className="size-3 text-muted-foreground" />
                                <span className="text-xs text-muted-foreground">
                                    Vento
                                </span>
                            </div>
                            <span className="text-xs font-bold">
                                {mockClimaMapa.ventoKmh} km/h
                            </span>
                        </div>
                        <div className="border-t border-border pt-1.5">
                            <div className="flex items-center gap-1.5">
                                <CloudRain className="size-3 text-muted-foreground" />
                                <span className="text-[10px] text-muted-foreground">
                                    {mockClimaMapa.previsaoChuva}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="absolute right-4 bottom-4 z-[1000] rounded-lg border border-border bg-card/95 p-3 shadow-md backdrop-blur-sm">
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
                            className="absolute top-14 left-4 z-[1000] max-h-[calc(100%-7rem)] w-80 overflow-auto rounded-xl border border-border bg-card/95 shadow-lg backdrop-blur-sm"
                        >
                            <div className="space-y-3 p-4">
                                <h3 className="text-sm font-semibold">
                                    Ocorrências ({mockIncendiosMapa.length})
                                </h3>
                                {mockIncendiosMapa.map((inc) => (
                                    <div
                                        key={inc.id}
                                        className="cursor-pointer rounded-lg bg-secondary/50 p-3 transition-colors hover:bg-secondary/80"
                                    >
                                        <div className="mb-1 flex flex-wrap items-center gap-2">
                                            <span className="text-sm font-medium">
                                                {inc.area_nome}
                                            </span>
                                            <StatusBadge status={inc.status} />
                                        </div>
                                        <p className="line-clamp-2 text-xs text-muted-foreground">
                                            {inc.descricao}
                                        </p>
                                        <div className="mt-2 flex items-center justify-between">
                                            <RiskBadge nivel={inc.nivel_risco} />
                                            <span className="text-xs text-muted-foreground">
                                                {new Date(
                                                    inc.criado_em,
                                                ).toLocaleDateString('pt-BR')}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </motion.div>
                    ) : null}
                </AnimatePresence>
            </div>
        </AppLayout>
    );
}
