import { Head } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Bell,
    Droplets,
    Flame,
    MapPin,
    ShieldAlert,
    Thermometer,
    TrendingUp,
    Users,
} from 'lucide-react';

import { RiskBadge } from '@/components/risk-badge';
import { StatsCard } from '@/components/stats-card';
import { StatusBadge } from '@/components/status-badge';
import {
    mockAlertas,
    mockEstatisticas,
    mockIncendios,
} from '@/data/dashboard-mock';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { DashboardDados } from '@/types/dashboard';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardProps {
    dados: DashboardDados;
}

export default function Dashboard({ dados }: DashboardProps) {
    const stats = mockEstatisticas;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="space-y-6 p-4 lg:p-6">
                <motion.div
                    initial={{ opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.35 }}
                >
                    <h1 className="text-2xl font-bold">Painel de Controle</h1>
                    <p className="text-sm text-muted-foreground">
                        Serra do Amolar — Corumbá, MS
                    </p>
                    {dados.ultimo_registro ? (
                        <p className="mt-1 text-xs text-muted-foreground">
                            Último registro de incêndio:{' '}
                            {new Date(dados.ultimo_registro).toLocaleString(
                                'pt-BR',
                            )}
                        </p>
                    ) : null}
                </motion.div>

                <motion.div
                    className="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4"
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.4, delay: 0.1 }}
                >
                    <StatsCard
                        label="Incêndios Ativos"
                        value={dados.incendios.ativos}
                        icon={Flame}
                        variant="critical"
                    />
                    <StatsCard
                        label="Alertas Pendentes"
                        value={dados.alertas.nao_entregues}
                        icon={Bell}
                        variant="warning"
                    />
                    <StatsCard
                        label="Ocorrências Contidas"
                        value={dados.incendios.contidos}
                        icon={ShieldAlert}
                    />
                    <StatsCard
                        label="Incêndios Resolvidos"
                        value={dados.incendios.resolvidos}
                        icon={Users}
                        variant="success"
                    />
                </motion.div>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <motion.div
                        className="glass-panel rounded-xl p-5"
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4, delay: 0.2 }}
                    >
                        <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold">
                            <TrendingUp className="size-4 text-primary" />
                            Condições Climáticas
                        </h3>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Thermometer className="size-4 text-critical" />
                                    <span className="text-sm text-muted-foreground">
                                        Temperatura
                                    </span>
                                </div>
                                <span className="text-lg font-bold text-critical">
                                    {stats.temperatura_media}°C
                                </span>
                            </div>
                            <div className="h-2 w-full rounded-full bg-secondary">
                                <div
                                    className="h-2 rounded-full bg-linear-to-r from-warning to-critical"
                                    style={{
                                        width: `${(stats.temperatura_media / 50) * 100}%`,
                                    }}
                                />
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Droplets className="size-4 text-primary" />
                                    <span className="text-sm text-muted-foreground">
                                        Umidade
                                    </span>
                                </div>
                                <span className="text-lg font-bold text-warning">
                                    {stats.umidade_media}%
                                </span>
                            </div>
                            <div className="h-2 w-full rounded-full bg-secondary">
                                <div
                                    className="h-2 rounded-full bg-linear-to-r from-critical to-warning"
                                    style={{
                                        width: `${stats.umidade_media}%`,
                                    }}
                                />
                            </div>

                            <div className="mt-2 rounded-lg border border-critical/20 bg-critical/10 p-3">
                                <p className="text-xs font-medium text-critical">
                                    Risco extremo de incêndio — umidade abaixo
                                    de 25%
                                </p>
                            </div>
                        </div>
                    </motion.div>

                    <motion.div
                        className="glass-panel rounded-xl p-5 lg:col-span-2"
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.4, delay: 0.3 }}
                    >
                        <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold">
                            <Flame className="size-4 text-primary" />
                            Ocorrências Recentes
                        </h3>
                        <div className="space-y-3">
                            {mockIncendios.map((inc) => (
                                <div
                                    key={inc.id}
                                    className="flex items-start gap-3 rounded-lg bg-secondary/50 p-3 transition-colors hover:bg-secondary/80"
                                >
                                    <div className="mt-0.5">
                                        <MapPin className="size-4 text-muted-foreground" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="text-sm font-medium">
                                                {inc.area_nome}
                                            </span>
                                            <StatusBadge status={inc.status} />
                                            <RiskBadge
                                                nivel={inc.nivel_risco}
                                            />
                                        </div>
                                        <p className="mt-1 line-clamp-1 text-xs text-muted-foreground">
                                            {inc.descricao}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {new Date(
                                                inc.criado_em,
                                            ).toLocaleString('pt-BR')}{' '}
                                            — {inc.registrado_por}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </motion.div>
                </div>

                <motion.div
                    className="glass-panel rounded-xl p-5"
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.4, delay: 0.4 }}
                >
                    <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold">
                        <Bell className="size-4 text-primary" />
                        Alertas Recentes
                    </h3>
                    <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                        {mockAlertas.map((alerta) => (
                            <div
                                key={alerta.id}
                                className={`rounded-lg border p-3 transition-colors ${
                                    alerta.lido
                                        ? 'border-border bg-secondary/30'
                                        : 'border-primary/20 bg-primary/5'
                                }`}
                            >
                                <div className="flex items-start gap-2">
                                    {!alerta.lido ? (
                                        <span className="status-pulse mt-1.5 size-2 shrink-0 rounded-full bg-primary" />
                                    ) : null}
                                    <div>
                                        <p className="text-sm font-medium">
                                            {alerta.mensagem}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {new Date(
                                                alerta.criado_em,
                                            ).toLocaleString('pt-BR')}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </motion.div>
            </div>
        </AppLayout>
    );
}
