import { Head } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Clock,
    Flame,
    MapPin,
    Navigation,
    UserCheck,
    UserX,
    Users,
} from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import {
    mockBrigadas,
    mockDespachos,
    mockIncendiosMapa,
} from '@/data/operacoes-mock';
import { brigadas as brigadasRoute } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { StatusBrigada } from '@/types/operacoes';

const statusConfig: Record<
    StatusBrigada,
    {
        label: string;
        className: string;
        icon: typeof UserCheck;
    }
> = {
    disponivel: {
        label: 'Disponível',
        className: 'border-resolved/30 bg-resolved/15 text-resolved',
        icon: UserCheck,
    },
    em_campo: {
        label: 'Em Campo',
        className: 'border-warning/30 bg-warning/15 text-warning',
        icon: MapPin,
    },
    indisponivel: {
        label: 'Indisponível',
        className: 'border-border bg-muted text-muted-foreground',
        icon: UserX,
    },
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Brigadas', href: brigadasRoute().url },
];

export default function Brigadas() {
    const getDestinoAtivo = (brigadaNome: string) => {
        const despacho = mockDespachos.find(
            (d) => d.brigada_nome === brigadaNome && !d.finalizado_em,
        );
        if (!despacho) {
            return null;
        }
        const incendio = mockIncendiosMapa.find(
            (i) => i.id === despacho.incendio_id,
        );
        return { despacho, incendio };
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Brigadas" />
            <div className="space-y-6 p-4 lg:p-6">
                <motion.div
                    initial={{ opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                >
                    <h1 className="text-2xl font-bold">Brigadas</h1>
                    <p className="text-sm text-muted-foreground">
                        Equipes de combate a incêndio
                    </p>
                </motion.div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    {mockBrigadas.map((brigada, i) => {
                        const st = statusConfig[brigada.status];
                        const Icon = st.icon;
                        const destino = getDestinoAtivo(brigada.nome);

                        return (
                            <motion.div
                                key={brigada.id}
                                initial={{ opacity: 0, y: 10 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ delay: i * 0.1 }}
                                className="glass-panel rounded-xl p-5 transition-shadow hover:shadow-md"
                            >
                                <div className="mb-3 flex items-start justify-between">
                                    <div>
                                        <h3 className="text-lg font-bold">
                                            {brigada.nome}
                                        </h3>
                                        <p className="flex items-center gap-1 text-sm text-muted-foreground">
                                            <MapPin className="size-3" />
                                            {brigada.regiao}
                                        </p>
                                    </div>
                                    <span
                                        className={cn(
                                            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold',
                                            st.className,
                                        )}
                                    >
                                        <Icon className="size-3" />
                                        {st.label}
                                    </span>
                                </div>

                                <div className="mt-3 flex items-center gap-4">
                                    <div className="flex items-center gap-2">
                                        <Users className="size-4 text-muted-foreground" />
                                        <span className="text-sm">
                                            {brigada.membros} membros
                                        </span>
                                    </div>
                                </div>

                                {destino ? (
                                    <div className="mt-3 rounded-lg border border-warning/20 bg-warning/10 p-3">
                                        <div className="mb-1 flex items-center gap-2">
                                            <Navigation className="size-3.5 text-warning" />
                                            <span className="text-xs font-semibold text-warning">
                                                {destino.despacho.chegada_em
                                                    ? 'No local'
                                                    : 'Em deslocamento'}
                                            </span>
                                        </div>
                                        {destino.incendio ? (
                                            <>
                                                <div className="mt-1 flex items-center gap-2">
                                                    <Flame className="size-3 text-critical" />
                                                    <span className="text-xs font-medium">
                                                        {
                                                            destino.incendio
                                                                .area_nome
                                                        }
                                                    </span>
                                                </div>
                                                <p className="mt-1 line-clamp-1 text-[11px] text-muted-foreground">
                                                    {destino.incendio.descricao}
                                                </p>
                                            </>
                                        ) : null}
                                        <p className="mt-1 text-[11px] text-muted-foreground">
                                            Despachado:{' '}
                                            {new Date(
                                                destino.despacho.despachado_em,
                                            ).toLocaleString('pt-BR')}
                                        </p>
                                    </div>
                                ) : null}
                            </motion.div>
                        );
                    })}
                </div>

                <motion.div
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.4 }}
                    className="glass-panel rounded-xl p-5"
                >
                    <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold">
                        <Clock className="size-4 text-primary" />
                        Despachos Recentes
                    </h3>
                    <div className="space-y-3">
                        {mockDespachos.map((despacho) => {
                            const incendio = mockIncendiosMapa.find(
                                (i) => i.id === despacho.incendio_id,
                            );
                            return (
                                <div
                                    key={despacho.id}
                                    className="rounded-lg bg-secondary/50 p-3"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <span className="text-sm font-medium">
                                                {despacho.brigada_nome}
                                            </span>
                                            {incendio ? (
                                                <span className="ml-2 text-xs text-muted-foreground">
                                                    → {incendio.area_nome}
                                                </span>
                                            ) : null}
                                        </div>
                                        <span
                                            className={cn(
                                                'rounded-full px-2 py-0.5 text-xs font-semibold',
                                                despacho.finalizado_em
                                                    ? 'bg-resolved/15 text-resolved'
                                                    : 'bg-warning/15 text-warning',
                                            )}
                                        >
                                            {despacho.finalizado_em
                                                ? 'Finalizado'
                                                : 'Em andamento'}
                                        </span>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </motion.div>
            </div>
        </AppLayout>
    );
}
