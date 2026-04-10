import { Head } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Bell,
    Check,
    Droplets,
    Filter,
    Flame,
    MapPin,
    Thermometer,
} from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { mockAlertasDetalhados } from '@/data/operacoes-mock';
import { alertas as alertasRoute } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { TipoAlerta } from '@/types/operacoes';

const tipoConfig: Record<
    TipoAlerta,
    { icon: typeof Flame; label: string; color: string }
> = {
    fogo_detectado: {
        icon: Flame,
        label: 'Fogo Detectado',
        color: 'text-critical',
    },
    temperatura_alta: {
        icon: Thermometer,
        label: 'Temperatura Alta',
        color: 'text-warning',
    },
    umidade_baixa: {
        icon: Droplets,
        label: 'Umidade Baixa',
        color: 'text-warning',
    },
    proximidade_local_critico: {
        icon: MapPin,
        label: 'Local Crítico',
        color: 'text-critical',
    },
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Alertas', href: alertasRoute().url },
];

export default function Alertas() {
    const [alertas, setAlertas] = useState(mockAlertasDetalhados);
    const [filtro, setFiltro] = useState<TipoAlerta | null>(null);

    const marcarLido = (id: string) => {
        setAlertas((prev) =>
            prev.map((a) => (a.id === id ? { ...a, lido: true } : a)),
        );
    };

    const marcarTodosLidos = () => {
        setAlertas((prev) => prev.map((a) => ({ ...a, lido: true })));
    };

    const alertasFiltrados = filtro
        ? alertas.filter((a) => a.tipo === filtro)
        : alertas;
    const naoLidos = alertas.filter((a) => !a.lido).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Alertas" />
            <div className="space-y-6 p-4 lg:p-6">
                <motion.div
                    initial={{ opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold">
                            Alertas
                            {naoLidos > 0 ? (
                                <span className="inline-flex size-6 items-center justify-center rounded-full bg-critical text-xs font-bold text-critical-foreground">
                                    {naoLidos}
                                </span>
                            ) : null}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Notificações automáticas do sistema
                        </p>
                    </div>
                    {naoLidos > 0 ? (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={marcarTodosLidos}
                            className="gap-2"
                        >
                            <Check className="size-4" />
                            Marcar todos como lidos
                        </Button>
                    ) : null}
                </motion.div>

                <div className="flex items-center gap-2 overflow-x-auto pb-2">
                    <Filter className="size-4 shrink-0 text-muted-foreground" />
                    <button
                        type="button"
                        onClick={() => setFiltro(null)}
                        className={cn(
                            'shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                            !filtro
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
                        )}
                    >
                        Todos
                    </button>
                    {(Object.keys(tipoConfig) as TipoAlerta[]).map((key) => {
                        const cfg = tipoConfig[key];
                        return (
                            <button
                                key={key}
                                type="button"
                                onClick={() =>
                                    setFiltro(filtro === key ? null : key)
                                }
                                className={cn(
                                    'shrink-0 rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                                    filtro === key
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
                                )}
                            >
                                {cfg.label}
                            </button>
                        );
                    })}
                </div>

                <div className="space-y-3">
                    {alertasFiltrados.map((alerta, i) => {
                        const cfg = tipoConfig[alerta.tipo];
                        const Icon = cfg.icon;
                        return (
                            <motion.div
                                key={alerta.id}
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: i * 0.05 }}
                                className={cn(
                                    'glass-panel flex items-start gap-4 rounded-xl p-4 transition-all',
                                    !alerta.lido &&
                                        'border-primary/20 bg-primary/5',
                                )}
                            >
                                <div
                                    className={cn(
                                        'rounded-lg bg-secondary p-2.5',
                                        cfg.color,
                                    )}
                                >
                                    <Icon className="size-5" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="mb-1 flex items-center gap-2">
                                        <span
                                            className={cn(
                                                'text-xs font-semibold tracking-wider uppercase',
                                                cfg.color,
                                            )}
                                        >
                                            {cfg.label}
                                        </span>
                                        {!alerta.lido ? (
                                            <span className="status-pulse size-2 rounded-full bg-primary" />
                                        ) : null}
                                    </div>
                                    <p className="text-sm">{alerta.mensagem}</p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {new Date(
                                            alerta.criado_em,
                                        ).toLocaleString('pt-BR')}
                                    </p>
                                </div>
                                {!alerta.lido ? (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            marcarLido(alerta.id)
                                        }
                                        className="shrink-0"
                                    >
                                        <Check className="size-4" />
                                    </Button>
                                ) : null}
                            </motion.div>
                        );
                    })}
                </div>

                {alertasFiltrados.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-2 py-12 text-muted-foreground">
                        <Bell className="size-10 opacity-40" />
                        <p className="text-sm">Nenhum alerta neste filtro.</p>
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}
