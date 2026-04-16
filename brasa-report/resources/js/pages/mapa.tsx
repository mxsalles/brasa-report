import { Head } from '@inertiajs/react';
import { AnimatePresence, motion } from 'framer-motion';
import {
    AlertTriangle,
    Check,
    CloudRain,
    Droplets,
    Flame,
    Layers,
    List,
    Loader,
    Thermometer,
    Wind,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { MapComponent } from '@/components/map-component';
import { RiskBadge } from '@/components/risk-badge';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    mockClimaMapa,
} from '@/data/operacoes-mock';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios-setup';
import { mapa as mapaRoute } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { StatusIncendio } from '@/types/dashboard';
import type { IncendioMapa } from '@/types/operacoes';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mapa', href: mapaRoute().url },
];

export default function Mapa() {
    const [painelAberto, setPainelAberto] = useState(false);
    const [incendios, setIncendios] = useState<IncendioMapa[]>([]);
    const [carregando, setCarregando] = useState(true);
    const [incendioSelecionado, setIncendioSelecionado] = useState<IncendioMapa | null>(null);
    const [modalAberto, setModalAberto] = useState(false);
    const [atualizandoStatus, setAtualizandoStatus] = useState(false);

    // Buscar incêndios da API
    useEffect(() => {
        const carregarIncendios = async () => {
            try {
                setCarregando(true);
                const response = await axios.get('/api/incendios');
                const dados = response.data.data;

                // Transformar para o tipo IncendioMapa
                const incendiosMapeados: IncendioMapa[] = dados.map((inc: any) => ({
                    id: inc.id,
                    latitude: parseFloat(inc.latitude),
                    longitude: parseFloat(inc.longitude),
                    status: inc.status,
                    nivel_risco: inc.nivel_risco,
                    descricao: inc.local_critico?.descricao || `Incêndio detectado em ${inc.area?.nome || 'área desconhecida'}`,
                    area_nome: inc.area?.nome || 'Área desconhecida',
                    registrado_por: inc.usuario?.nome || 'Desconhecido',
                    criado_em: inc.detectado_em || new Date().toISOString(),
                    tipo_local_critico: inc.local_critico?.tipo || null,
                }));

                setIncendios(incendiosMapeados);
            } catch (error) {
                console.error('Erro ao carregar incêndios:', error);
                toast.error('Erro ao carregar incêndios do mapa');
            } finally {
                setCarregando(false);
            }
        };

        carregarIncendios();
    }, []);

    const abrirDetalhes = useCallback((incendio: IncendioMapa) => {
        setIncendioSelecionado(incendio);
        setModalAberto(true);
    }, []);

    const fecharModal = useCallback(() => {
        setModalAberto(false);
        setTimeout(() => setIncendioSelecionado(null), 300);
    }, []);

    const atualizarStatusIncendio = async (novoStatus: StatusIncendio) => {
        if (!incendioSelecionado) return;

        try {
            setAtualizandoStatus(true);
            
            // Atualizar status via API (logs são criados automaticamente no backend)
            await axios.patch(`/api/incendios/${incendioSelecionado.id}/status`, {
                status: novoStatus,
            });

            // Atualizar estado local
            const incendioAtualizado = { ...incendioSelecionado, status: novoStatus };
            setIncendios(incendios.map((inc: IncendioMapa) => inc.id === incendioSelecionado.id ? incendioAtualizado : inc));
            setIncendioSelecionado(incendioAtualizado);

            toast.success(`Status atualizado para "${novoStatus}"`);
        } catch (error) {
            console.error('Erro ao atualizar status:', error);
            toast.error('Erro ao atualizar status do incêndio');
        } finally {
            setAtualizandoStatus(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mapa" />
            <div className="relative h-[calc(100dvh-5rem)] min-h-[420px] w-full overflow-hidden rounded-xl border border-border/60">
                {carregando ? (
                    <div className="absolute inset-0 z-[500] flex items-center justify-center bg-background/80 backdrop-blur-sm">
                        <div className="flex flex-col items-center gap-3">
                            <Loader className="size-8 animate-spin text-primary" />
                            <p className="text-sm text-muted-foreground">Carregando incêndios...</p>
                        </div>
                    </div>
                ) : null}

                <MapComponent
                    incendios={incendios}
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

                {/* Painel de Ocorrências */}
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
                                    Ocorrências ({incendios.length})
                                </h3>
                                {incendios.length === 0 ? (
                                    <p className="text-center text-xs text-muted-foreground py-4">
                                        Nenhum incêndio registrado no momento
                                    </p>
                                ) : (
                                    incendios.map((inc) => (
                                        <motion.div
                                            key={inc.id}
                                            onClick={() => abrirDetalhes(inc)}
                                            whileHover={{ scale: 1.02 }}
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
                                        </motion.div>
                                    ))
                                )}
                            </div>
                        </motion.div>
                    ) : null}
                </AnimatePresence>

                {/* Modal de Detalhes e Interação */}
                <AnimatePresence>
                    {modalAberto && incendioSelecionado ? (
                        <motion.div
                            key="modal-backdrop"
                            initial={{ opacity: 0 }}
                            animate={{ opacity: 1 }}
                            exit={{ opacity: 0 }}
                            className="absolute inset-0 z-[2000] flex items-center justify-center bg-black/50 backdrop-blur-sm"
                            onClick={fecharModal}
                        >
                            <motion.div
                                initial={{ scale: 0.95, opacity: 0 }}
                                animate={{ scale: 1, opacity: 1 }}
                                exit={{ scale: 0.95, opacity: 0 }}
                                onClick={(e) => e.stopPropagation()}
                                className="relative w-full max-w-md rounded-xl border border-border bg-card p-6 shadow-lg"
                            >
                                <div className="mb-4 flex items-start justify-between">
                                    <div>
                                        <h2 className="text-lg font-bold">{incendioSelecionado.area_nome}</h2>
                                        <p className="text-xs text-muted-foreground">
                                            ID: {incendioSelecionado.id.slice(0, 8)}...
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={fecharModal}
                                        className="h-8 w-8"
                                    >
                                        <X className="size-4" />
                                    </Button>
                                </div>

                                {/* Informações do Incêndio */}
                                <div className="mb-4 space-y-3 rounded-lg bg-secondary/30 p-4">
                                    <div className="flex items-center gap-2">
                                        <span className="text-xs font-semibold text-muted-foreground">Status:</span>
                                        <StatusBadge status={incendioSelecionado.status} />
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-xs font-semibold text-muted-foreground">Risco:</span>
                                        <RiskBadge nivel={incendioSelecionado.nivel_risco} />
                                    </div>
                                    <div>
                                        <span className="text-xs font-semibold text-muted-foreground">Descrição:</span>
                                        <p className="mt-1 text-xs leading-relaxed">{incendioSelecionado.descricao}</p>
                                    </div>
                                    <div>
                                        <span className="text-xs font-semibold text-muted-foreground">Registrado por:</span>
                                        <p className="mt-1 text-xs">{incendioSelecionado.registrado_por}</p>
                                    </div>
                                    <div className="grid grid-cols-2 gap-2">
                                        <div>
                                            <span className="text-xs font-semibold text-muted-foreground">Latitude:</span>
                                            <p className="mt-1 font-mono text-xs">{incendioSelecionado.latitude.toFixed(6)}</p>
                                        </div>
                                        <div>
                                            <span className="text-xs font-semibold text-muted-foreground">Longitude:</span>
                                            <p className="mt-1 font-mono text-xs">{incendioSelecionado.longitude.toFixed(6)}</p>
                                        </div>
                                    </div>
                                    <div>
                                        <span className="text-xs font-semibold text-muted-foreground">Data/Hora:</span>
                                        <p className="mt-1 text-xs">
                                            {new Date(incendioSelecionado.criado_em).toLocaleDateString('pt-BR', {
                                                day: '2-digit',
                                                month: '2-digit',
                                                year: 'numeric',
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            })}
                                        </p>
                                    </div>
                                </div>

                                {/* Ações - Mudar Status */}
                                <div className="mb-4 space-y-2">
                                    <p className="text-xs font-semibold text-muted-foreground">Alterar Status:</p>
                                    <div className="space-y-2">
                                        {incendioSelecionado.status !== 'ativo' ? (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="w-full gap-2"
                                                onClick={() => atualizarStatusIncendio('ativo')}
                                                disabled={atualizandoStatus}
                                            >
                                                <Flame className="size-3" />
                                                {atualizandoStatus && incendioSelecionado.status === 'ativo' ? (
                                                    <Loader className="size-3 animate-spin" />
                                                ) : (
                                                    'Marcar como Ativo'
                                                )}
                                            </Button>
                                        ) : null}

                                        {incendioSelecionado.status !== 'contido' ? (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="w-full gap-2"
                                                onClick={() => atualizarStatusIncendio('contido')}
                                                disabled={atualizandoStatus}
                                            >
                                                <AlertTriangle className="size-3" />
                                                {atualizandoStatus && incendioSelecionado.status === 'contido' ? (
                                                    <Loader className="size-3 animate-spin" />
                                                ) : (
                                                    'Marcar como Contido'
                                                )}
                                            </Button>
                                        ) : null}

                                        {incendioSelecionado.status !== 'resolvido' ? (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="w-full gap-2"
                                                onClick={() => atualizarStatusIncendio('resolvido')}
                                                disabled={atualizandoStatus}
                                            >
                                                <Check className="size-3" />
                                                {atualizandoStatus && incendioSelecionado.status === 'resolvido' ? (
                                                    <Loader className="size-3 animate-spin" />
                                                ) : (
                                                    'Marcar como Resolvido'
                                                )}
                                            </Button>
                                        ) : null}
                                    </div>
                                </div>

                                <Button
                                    variant="default"
                                    className="w-full"
                                    onClick={fecharModal}
                                >
                                    Fechar
                                </Button>
                            </motion.div>
                        </motion.div>
                    ) : null}
                </AnimatePresence>
            </div>
        </AppLayout>
    );
}
