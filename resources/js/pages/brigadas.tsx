import { Head, router, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle,
    Clock,
    Flame,
    ListChecks,
    MapPin,
    Pencil,
    Plus,
    Search,
    Send,
    Trash2,
    UserCheck,
    UserX,
    Users,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { toast } from 'sonner';

import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios-setup';
import { cn } from '@/lib/utils';
import { brigadas as brigadasRoute } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { FuncaoUsuario } from '@/types/auth';
import type { StatusIncendio } from '@/types/dashboard';

type OperacaoIncendioBrigada = {
    fase: 'em_deslocamento' | 'em_combate';
    incendio_status: StatusIncendio;
    area_nome: string;
};

type BrigadaItem = {
    id: string;
    nome: string;
    tipo: string;
    latitude_atual: string | null;
    longitude_atual: string | null;
    disponivel: boolean;
    usuarios_count?: number;
    operacao_incendio: OperacaoIncendioBrigada | null;
};

type DespachoItem = {
    id: string;
    incendio_id: string;
    brigada_nome: string;
    incendio_area_nome: string;
    despachado_em: string | null;
    chegada_em: string | null;
    finalizado_em: string | null;
    observacoes: string | null;
};

type UsuarioDisponivel = {
    id: string;
    nome: string;
    funcao: FuncaoUsuario;
};

type MembroRestrito = {
    id: string;
    nome: string;
    funcao: FuncaoUsuario;
};

type BrigadaDetalhe = {
    id: string;
    nome: string;
    tipo: string;
    latitude_atual: string | null;
    longitude_atual: string | null;
    disponivel: boolean;
    membros: MembroRestrito[];
};

type IncendioAtivo = {
    id: string;
    latitude: string;
    longitude: string;
    detectado_em: string | null;
    nivel_risco: string;
    status: string;
    area_nome: string;
};

type PageProps = {
    brigadas: BrigadaItem[];
    despachosAtivos: DespachoItem[];
    despachosFinalizados: DespachoItem[];
    podeGerenciar: boolean;
    funcaoAutenticado: FuncaoUsuario;
    usuariosDisponiveis: UsuarioDisponivel[];
    incendiosAtivos: IncendioAtivo[];
};

type FormData = {
    nome: string;
    tipo: string;
    disponivel: boolean;
};

const emptyForm: FormData = {
    nome: '',
    tipo: '',
    disponivel: true,
};

const funcaoLabel: Record<string, string> = {
    user: 'Usuário',
    brigadista: 'Brigadista',
    gestor: 'Gestor',
    administrador: 'Administrador',
};

const funcaoBadge: Record<string, string> = {
    user: 'border-slate-200 bg-slate-100 text-slate-700',
    brigadista: 'border-emerald-200 bg-emerald-100 text-emerald-700',
    gestor: 'border-blue-200 bg-blue-100 text-blue-700',
    administrador: 'border-purple-200 bg-purple-100 text-purple-700',
};

const nivelRiscoLabel: Record<string, string> = {
    baixo: 'Baixo',
    moderado: 'Moderado',
    alto: 'Alto',
    critico: 'Crítico',
};

const nivelRiscoBadge: Record<string, string> = {
    baixo: 'border-emerald-200 bg-emerald-100 text-emerald-700',
    moderado: 'border-amber-200 bg-amber-100 text-amber-700',
    alto: 'border-orange-200 bg-orange-100 text-orange-700',
    critico: 'border-red-200 bg-red-100 text-red-700',
};

const statusIncendioLabel: Record<string, string> = {
    ativo: 'Ativo',
    contido: 'Contido',
    resolvido: 'Resolvido',
};

const statusIncendioBadge: Record<string, string> = {
    ativo: 'border-red-200 bg-red-100 text-red-700',
    contido: 'border-amber-200 bg-amber-100 text-amber-700',
    resolvido: 'border-emerald-200 bg-emerald-100 text-emerald-700',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Brigadas', href: brigadasRoute().url },
];

function extractErrorMessage(err: unknown, fallback: string): string {
    if (
        err &&
        typeof err === 'object' &&
        'response' in err &&
        err.response &&
        typeof err.response === 'object' &&
        'data' in err.response &&
        err.response.data &&
        typeof err.response.data === 'object' &&
        'message' in err.response.data &&
        typeof err.response.data.message === 'string'
    ) {
        return err.response.data.message;
    }
    return fallback;
}

export default function Brigadas() {
    const {
        brigadas,
        despachosAtivos,
        despachosFinalizados,
        podeGerenciar,
        usuariosDisponiveis,
        incendiosAtivos,
    } = usePage<PageProps>().props;

    const [aba, setAba] = useState<'brigadas' | 'despachos'>('brigadas');

    const [detailOpen, setDetailOpen] = useState(false);
    const [detailData, setDetailData] = useState<BrigadaDetalhe | null>(null);
    const [detailLoading, setDetailLoading] = useState(false);

    const [formOpen, setFormOpen] = useState(false);
    const [formData, setFormData] = useState<FormData>(emptyForm);
    const [editingId, setEditingId] = useState<string | null>(null);
    const [formSubmitting, setFormSubmitting] = useState(false);
    const [formErrors, setFormErrors] = useState<Record<string, string[]>>({});

    const [selectedMemberIds, setSelectedMemberIds] = useState<Set<string>>(
        new Set(),
    );
    const [originalMemberIds, setOriginalMemberIds] = useState<Set<string>>(
        new Set(),
    );
    const [editMembros, setEditMembros] = useState<UsuarioDisponivel[]>([]);
    const [memberSearch, setMemberSearch] = useState('');
    const [formLoading, setFormLoading] = useState(false);

    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deletingBrigada, setDeletingBrigada] = useState<BrigadaItem | null>(
        null,
    );
    const [deleteLoading, setDeleteLoading] = useState(false);

    const [dispatchOpen, setDispatchOpen] = useState(false);
    const [dispatchStep, setDispatchStep] = useState<1 | 2>(1);
    const [selectedIncendioId, setSelectedIncendioId] = useState<string | null>(
        null,
    );
    const [selectedBrigadaIds, setSelectedBrigadaIds] = useState<Set<string>>(
        new Set(),
    );
    const [dispatchSearch, setDispatchSearch] = useState('');
    const [dispatchSubmitting, setDispatchSubmitting] = useState(false);

    const [despachoDetailOpen, setDespachoDetailOpen] = useState(false);
    const [selectedDespacho, setSelectedDespacho] =
        useState<DespachoItem | null>(null);
    const [despachoActionLoading, setDespachoActionLoading] = useState(false);
    const [finalizarObs, setFinalizarObs] = useState('');

    const candidateMembros = useMemo(() => {
        const all: UsuarioDisponivel[] = [
            ...editMembros,
            ...usuariosDisponiveis.filter(
                (u) => !editMembros.some((m) => m.id === u.id),
            ),
        ];
        if (!memberSearch.trim()) return all;
        const q = memberSearch.toLowerCase();
        return all.filter((u) => u.nome.toLowerCase().includes(q));
    }, [usuariosDisponiveis, editMembros, memberSearch]);

    const toggleMember = useCallback((id: string) => {
        setSelectedMemberIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    }, []);

    const openDetail = useCallback(async (id: string) => {
        setDetailOpen(true);
        setDetailLoading(true);
        setDetailData(null);
        try {
            const res = await axios.get(`/api/brigadas/${id}`);
            setDetailData(res.data.data);
        } catch {
            toast.error('Não foi possível carregar os detalhes da brigada.');
            setDetailOpen(false);
        } finally {
            setDetailLoading(false);
        }
    }, []);

    const openCreate = useCallback(() => {
        setEditingId(null);
        setFormData(emptyForm);
        setFormErrors({});
        setSelectedMemberIds(new Set());
        setOriginalMemberIds(new Set());
        setEditMembros([]);
        setMemberSearch('');
        setFormLoading(false);
        setFormOpen(true);
    }, []);

    const openEdit = useCallback(
        async (e: React.MouseEvent, brigada: BrigadaItem) => {
            e.stopPropagation();
            if (brigada.operacao_incendio) {
                return;
            }
            setEditingId(brigada.id);
            setFormData({
                nome: brigada.nome,
                tipo: brigada.tipo,
                disponivel: brigada.disponivel,
            });
            setFormErrors({});
            setMemberSearch('');
            setFormLoading(true);
            setFormOpen(true);

            try {
                const res = await axios.get(`/api/brigadas/${brigada.id}`);
                const membros: MembroRestrito[] =
                    res.data.data.membros ?? [];
                const ids = new Set(membros.map((m) => m.id));
                setSelectedMemberIds(ids);
                setOriginalMemberIds(new Set(ids));
                setEditMembros(membros);
            } catch {
                toast.error('Não foi possível carregar os membros.');
                setSelectedMemberIds(new Set());
                setOriginalMemberIds(new Set());
                setEditMembros([]);
            } finally {
                setFormLoading(false);
            }
        },
        [],
    );

    const openDelete = useCallback(
        (e: React.MouseEvent, brigada: BrigadaItem) => {
            e.stopPropagation();
            if (brigada.operacao_incendio) {
                return;
            }
            setDeletingBrigada(brigada);
            setDeleteOpen(true);
        },
        [],
    );

    const syncMembers = useCallback(
        async (brigadaId: string) => {
            const toAdd = [...selectedMemberIds].filter(
                (id) => !originalMemberIds.has(id),
            );
            const toRemove = [...originalMemberIds].filter(
                (id) => !selectedMemberIds.has(id),
            );

            const promises = [
                ...toAdd.map((uid) =>
                    axios.patch(`/api/usuarios/${uid}/brigada`, {
                        brigada_id: brigadaId,
                    }),
                ),
                ...toRemove.map((uid) =>
                    axios.patch(`/api/usuarios/${uid}/brigada`, {
                        brigada_id: null,
                    }),
                ),
            ];

            if (promises.length > 0) {
                await Promise.all(promises);
            }
        },
        [selectedMemberIds, originalMemberIds],
    );

    const submitForm = useCallback(async () => {
        setFormSubmitting(true);
        setFormErrors({});
        try {
            const payload = {
                nome: formData.nome,
                tipo: formData.tipo,
                disponivel: formData.disponivel,
            };

            let brigadaId = editingId;

            if (editingId) {
                await axios.put(`/api/brigadas/${editingId}`, payload);
            } else {
                const res = await axios.post('/api/brigadas', payload);
                brigadaId = res.data.data.id;
            }

            await syncMembers(brigadaId!);

            toast.success(
                editingId ? 'Brigada atualizada.' : 'Brigada criada.',
            );
            setFormOpen(false);
            router.reload();
        } catch (err: unknown) {
            if (
                err &&
                typeof err === 'object' &&
                'response' in err &&
                err.response &&
                typeof err.response === 'object' &&
                'status' in err.response &&
                err.response.status === 422 &&
                'data' in err.response &&
                err.response.data &&
                typeof err.response.data === 'object' &&
                'errors' in err.response.data
            ) {
                setFormErrors(
                    err.response.data.errors as Record<string, string[]>,
                );
            } else {
                toast.error(
                    extractErrorMessage(
                        err,
                        'Não foi possível salvar a brigada.',
                    ),
                );
            }
        } finally {
            setFormSubmitting(false);
        }
    }, [formData, editingId, syncMembers]);

    const confirmDelete = useCallback(async () => {
        if (!deletingBrigada) return;
        setDeleteLoading(true);
        try {
            await axios.delete(`/api/brigadas/${deletingBrigada.id}`);
            toast.success('Brigada removida.');
            setDeleteOpen(false);
            setDeletingBrigada(null);
            router.reload();
        } catch (err: unknown) {
            toast.error(
                extractErrorMessage(
                    err,
                    'Não foi possível remover a brigada.',
                ),
            );
        } finally {
            setDeleteLoading(false);
        }
    }, [deletingBrigada]);

    const openDispatch = useCallback(() => {
        setDispatchStep(1);
        setSelectedIncendioId(null);
        setSelectedBrigadaIds(new Set());
        setDispatchSearch('');
        setDispatchOpen(true);
    }, []);

    const selectedIncendio = useMemo(
        () =>
            incendiosAtivos?.find((i) => i.id === selectedIncendioId) ?? null,
        [incendiosAtivos, selectedIncendioId],
    );

    const filteredIncendios = useMemo(() => {
        if (!incendiosAtivos) return [];
        if (!dispatchSearch.trim()) return incendiosAtivos;
        const q = dispatchSearch.toLowerCase();
        return incendiosAtivos.filter(
            (i) =>
                i.area_nome.toLowerCase().includes(q) ||
                i.status.toLowerCase().includes(q) ||
                i.nivel_risco.toLowerCase().includes(q),
        );
    }, [incendiosAtivos, dispatchSearch]);

    const filteredDispatchBrigadas = useMemo(() => {
        if (!dispatchSearch.trim()) return brigadas;
        const q = dispatchSearch.toLowerCase();
        return brigadas.filter((b) => b.nome.toLowerCase().includes(q));
    }, [brigadas, dispatchSearch]);

    const toggleDispatchBrigada = useCallback((id: string) => {
        setSelectedBrigadaIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    }, []);

    const confirmDispatch = useCallback(async () => {
        if (!selectedIncendio || selectedBrigadaIds.size === 0) return;
        setDispatchSubmitting(true);
        try {
            const promises = [...selectedBrigadaIds].flatMap((brigadaId) => [
                axios.post(
                    `/api/incendios/${selectedIncendio.id}/despachos`,
                    { brigada_id: brigadaId },
                ),
                axios.patch(`/api/brigadas/${brigadaId}/localizacao`, {
                    latitude_atual: selectedIncendio.latitude,
                    longitude_atual: selectedIncendio.longitude,
                }),
            ]);

            await Promise.all(promises);

            const count = selectedBrigadaIds.size;
            toast.success(
                `${count} brigada${count !== 1 ? 's' : ''} despachada${count !== 1 ? 's' : ''} com sucesso.`,
            );
            setDispatchOpen(false);
            router.reload();
        } catch (err: unknown) {
            toast.error(
                extractErrorMessage(
                    err,
                    'Não foi possível completar o despacho.',
                ),
            );
        } finally {
            setDispatchSubmitting(false);
        }
    }, [selectedIncendio, selectedBrigadaIds]);

    const openDespachoDetail = useCallback((despacho: DespachoItem) => {
        setSelectedDespacho(despacho);
        setFinalizarObs('');
        setDespachoDetailOpen(true);
    }, []);

    const despachoStatus = useMemo(() => {
        if (!selectedDespacho) return null;
        if (selectedDespacho.finalizado_em) return 'finalizado' as const;
        if (selectedDespacho.chegada_em) return 'no_local' as const;
        return 'em_deslocamento' as const;
    }, [selectedDespacho]);

    const registrarChegada = useCallback(async () => {
        if (!selectedDespacho) return;
        setDespachoActionLoading(true);
        try {
            await axios.patch(
                `/api/incendios/${selectedDespacho.incendio_id}/despachos/${selectedDespacho.id}/chegada`,
                { chegada_em: new Date().toISOString() },
            );
            toast.success('Chegada registrada com sucesso.');
            setDespachoDetailOpen(false);
            router.reload();
        } catch (err: unknown) {
            toast.error(
                extractErrorMessage(err, 'Não foi possível registrar a chegada.'),
            );
        } finally {
            setDespachoActionLoading(false);
        }
    }, [selectedDespacho]);

    const finalizarDespacho = useCallback(async () => {
        if (!selectedDespacho) return;
        setDespachoActionLoading(true);
        try {
            await axios.patch(
                `/api/incendios/${selectedDespacho.incendio_id}/despachos/${selectedDespacho.id}/finalizar`,
                {
                    finalizado_em: new Date().toISOString(),
                    ...(finalizarObs.trim()
                        ? { observacoes: finalizarObs.trim() }
                        : {}),
                },
            );
            toast.success('Despacho finalizado com sucesso.');
            setDespachoDetailOpen(false);
            router.reload();
        } catch (err: unknown) {
            toast.error(
                extractErrorMessage(
                    err,
                    'Não foi possível finalizar o despacho.',
                ),
            );
        } finally {
            setDespachoActionLoading(false);
        }
    }, [selectedDespacho, finalizarObs]);

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
                        Equipes de combate a incêndio e despachos
                    </p>
                </motion.div>

                <div className="grid w-full max-w-md grid-cols-2 gap-2 rounded-lg border border-border bg-muted/40 p-1">
                    <button
                        type="button"
                        onClick={() => setAba('brigadas')}
                        className={cn(
                            'flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            aba === 'brigadas'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        <Users className="size-4" />
                        Brigadas
                    </button>
                    <button
                        type="button"
                        onClick={() => setAba('despachos')}
                        className={cn(
                            'flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            aba === 'despachos'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        <ListChecks className="size-4" />
                        Despachos
                    </button>
                </div>

                {aba === 'brigadas' && (
                    <>
                        {podeGerenciar && (
                            <div className="flex justify-end">
                                <Button onClick={openCreate}>
                                    <Plus className="size-4" />
                                    Nova Brigada
                                </Button>
                            </div>
                        )}

                        {brigadas.length === 0 ? (
                            <div className="glass-panel rounded-xl p-8 text-center text-muted-foreground">
                                Nenhuma brigada cadastrada.
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                {brigadas.map((brigada, i) => {
                                    const disponivel = brigada.disponivel;
                                    const emOperacao =
                                        brigada.operacao_incendio !== null;
                                    return (
                                        <motion.div
                                            key={brigada.id}
                                            initial={{ opacity: 0, y: 10 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            transition={{
                                                delay: i * 0.07,
                                            }}
                                            className="glass-panel cursor-pointer rounded-xl p-5 transition-shadow hover:shadow-md"
                                            onClick={() =>
                                                openDetail(brigada.id)
                                            }
                                            role="button"
                                            tabIndex={0}
                                            onKeyDown={(e) => {
                                                if (
                                                    e.key === 'Enter' ||
                                                    e.key === ' '
                                                )
                                                    openDetail(brigada.id);
                                            }}
                                        >
                                            <div className="mb-3 flex items-start justify-between">
                                                <div className="min-w-0 flex-1">
                                                    <h3 className="text-lg font-bold">
                                                        {brigada.nome}
                                                    </h3>
                                                    <p className="flex items-center gap-1 text-sm text-muted-foreground">
                                                        <MapPin className="size-3 shrink-0" />
                                                        {brigada.tipo}
                                                    </p>
                                                    {brigada.operacao_incendio && (
                                                        <div className="mt-2 space-y-1">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <StatusBadge
                                                                    status={
                                                                        brigada
                                                                            .operacao_incendio
                                                                            .incendio_status
                                                                    }
                                                                />
                                                                <span
                                                                    className={cn(
                                                                        'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold',
                                                                        brigada
                                                                            .operacao_incendio
                                                                            .fase ===
                                                                            'em_deslocamento'
                                                                            ? 'border-warning/30 bg-warning/15 text-warning'
                                                                            : 'border-contained/30 bg-contained/15 text-contained',
                                                                    )}
                                                                >
                                                                    {brigada
                                                                        .operacao_incendio
                                                                        .fase ===
                                                                    'em_deslocamento' ? (
                                                                        <Send className="size-3 shrink-0" />
                                                                    ) : (
                                                                        <Flame className="size-3 shrink-0" />
                                                                    )}
                                                                    {brigada
                                                                        .operacao_incendio
                                                                        .fase ===
                                                                    'em_deslocamento'
                                                                        ? 'Em deslocamento'
                                                                        : 'Em combate'}
                                                                </span>
                                                            </div>
                                                            <p className="text-xs text-muted-foreground">
                                                                →{' '}
                                                                {
                                                                    brigada
                                                                        .operacao_incendio
                                                                        .area_nome
                                                                }
                                                            </p>
                                                        </div>
                                                    )}
                                                </div>
                                                <span
                                                    className={cn(
                                                        'inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold',
                                                        disponivel
                                                            ? 'border-resolved/30 bg-resolved/15 text-resolved'
                                                            : 'border-border bg-muted text-muted-foreground',
                                                    )}
                                                >
                                                    {disponivel ? (
                                                        <UserCheck className="size-3" />
                                                    ) : (
                                                        <UserX className="size-3" />
                                                    )}
                                                    {disponivel
                                                        ? 'Disponível'
                                                        : 'Indisponível'}
                                                </span>
                                            </div>

                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <Users className="size-4 text-muted-foreground" />
                                                    <span className="text-sm">
                                                        {brigada.usuarios_count ??
                                                            0}{' '}
                                                        membro
                                                        {(brigada.usuarios_count ??
                                                            0) !== 1
                                                            ? 's'
                                                            : ''}
                                                    </span>
                                                </div>

                                                {podeGerenciar && (
                                                    <div className="flex items-center gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8"
                                                            disabled={emOperacao}
                                                            onClick={(e) =>
                                                                openEdit(
                                                                    e,
                                                                    brigada,
                                                                )
                                                            }
                                                            title={
                                                                emOperacao
                                                                    ? 'Indisponível enquanto a brigada estiver em deslocamento ou em combate'
                                                                    : 'Editar brigada'
                                                            }
                                                        >
                                                            <Pencil className="size-3.5" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8 text-destructive hover:text-destructive"
                                                            disabled={emOperacao}
                                                            onClick={(e) =>
                                                                openDelete(
                                                                    e,
                                                                    brigada,
                                                                )
                                                            }
                                                            title={
                                                                emOperacao
                                                                    ? 'Indisponível enquanto a brigada estiver em deslocamento ou em combate'
                                                                    : 'Remover brigada'
                                                            }
                                                        >
                                                            <Trash2 className="size-3.5" />
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                        </motion.div>
                                    );
                                })}
                            </div>
                        )}
                    </>
                )}

                {aba === 'despachos' && (
                    <>
                        {podeGerenciar && (
                            <div className="flex justify-end">
                                <Button
                                    variant="outline"
                                    onClick={openDispatch}
                                >
                                    <Send className="size-4" />
                                    Despachar Brigadas
                                </Button>
                            </div>
                        )}

                        <motion.div
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            className="glass-panel rounded-xl p-5"
                        >
                            <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold">
                                <Clock className="size-4 text-warning" />
                                Despachos Ativos
                            </h3>
                            {despachosAtivos.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Nenhum despacho ativo no momento.
                                </p>
                            ) : (
                                <div className="space-y-3">
                                    {despachosAtivos.map((d) => (
                                        <div
                                            key={d.id}
                                            className={cn(
                                                'rounded-lg bg-secondary/50 p-3',
                                                podeGerenciar &&
                                                    'cursor-pointer transition-colors hover:bg-secondary/80',
                                            )}
                                            onClick={
                                                podeGerenciar
                                                    ? () =>
                                                          openDespachoDetail(d)
                                                    : undefined
                                            }
                                            role={
                                                podeGerenciar
                                                    ? 'button'
                                                    : undefined
                                            }
                                            tabIndex={
                                                podeGerenciar ? 0 : undefined
                                            }
                                            onKeyDown={
                                                podeGerenciar
                                                    ? (e) => {
                                                          if (
                                                              e.key ===
                                                                  'Enter' ||
                                                              e.key === ' '
                                                          )
                                                              openDespachoDetail(
                                                                  d,
                                                              );
                                                      }
                                                    : undefined
                                            }
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <div>
                                                    <span className="text-sm font-medium">
                                                        {d.brigada_nome}
                                                    </span>
                                                    <span className="ml-2 text-xs text-muted-foreground">
                                                        →{' '}
                                                        {
                                                            d.incendio_area_nome
                                                        }
                                                    </span>
                                                </div>
                                                <span
                                                    className={cn(
                                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                                        d.chegada_em
                                                            ? 'bg-contained/15 text-contained'
                                                            : 'bg-warning/15 text-warning',
                                                    )}
                                                >
                                                    {d.chegada_em
                                                        ? 'No local'
                                                        : 'Em deslocamento'}
                                                </span>
                                            </div>
                                            {d.despachado_em && (
                                                <p className="mt-1 text-[11px] text-muted-foreground">
                                                    Despachado:{' '}
                                                    {new Date(
                                                        d.despachado_em,
                                                    ).toLocaleString('pt-BR')}
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </motion.div>

                        <motion.div
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.15 }}
                            className="glass-panel rounded-xl p-5"
                        >
                            <h3 className="mb-4 flex items-center gap-2 text-sm font-semibold">
                                <CheckCircle className="size-4 text-resolved" />
                                Histórico
                            </h3>
                            {despachosFinalizados.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Nenhum despacho finalizado.
                                </p>
                            ) : (
                                <div className="space-y-3">
                                    {despachosFinalizados.map((d) => (
                                        <div
                                            key={d.id}
                                            className="cursor-pointer rounded-lg bg-secondary/50 p-3 transition-colors hover:bg-secondary/80"
                                            onClick={() =>
                                                openDespachoDetail(d)
                                            }
                                            role="button"
                                            tabIndex={0}
                                            onKeyDown={(e) => {
                                                if (
                                                    e.key === 'Enter' ||
                                                    e.key === ' '
                                                )
                                                    openDespachoDetail(d);
                                            }}
                                        >
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <div>
                                                    <span className="text-sm font-medium">
                                                        {d.brigada_nome}
                                                    </span>
                                                    <span className="ml-2 text-xs text-muted-foreground">
                                                        →{' '}
                                                        {
                                                            d.incendio_area_nome
                                                        }
                                                    </span>
                                                </div>
                                                <span className="rounded-full bg-resolved/15 px-2 py-0.5 text-xs font-semibold text-resolved">
                                                    Finalizado
                                                </span>
                                            </div>
                                            {d.finalizado_em && (
                                                <p className="mt-1 text-[11px] text-muted-foreground">
                                                    Finalizado:{' '}
                                                    {new Date(
                                                        d.finalizado_em,
                                                    ).toLocaleString('pt-BR')}
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </motion.div>
                    </>
                )}
            </div>

            {/* Dialog de detalhes */}
            <Dialog open={detailOpen} onOpenChange={setDetailOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {detailData?.nome ?? 'Carregando...'}
                        </DialogTitle>
                        <DialogDescription>
                            Detalhes da brigada e membros
                        </DialogDescription>
                    </DialogHeader>

                    {detailLoading ? (
                        <div className="space-y-3 py-4">
                            <div className="h-4 w-3/4 animate-pulse rounded bg-muted" />
                            <div className="h-4 w-1/2 animate-pulse rounded bg-muted" />
                            <div className="h-4 w-2/3 animate-pulse rounded bg-muted" />
                        </div>
                    ) : detailData ? (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span className="text-muted-foreground">
                                        Tipo
                                    </span>
                                    <p className="font-medium">
                                        {detailData.tipo}
                                    </p>
                                </div>
                                <div>
                                    <span className="text-muted-foreground">
                                        Status
                                    </span>
                                    <p className="font-medium">
                                        {detailData.disponivel
                                            ? 'Disponível'
                                            : 'Indisponível'}
                                    </p>
                                </div>
                                {detailData.latitude_atual &&
                                    detailData.longitude_atual && (
                                        <div className="col-span-2">
                                            <span className="text-muted-foreground">
                                                Coordenadas
                                            </span>
                                            <p className="font-medium">
                                                {detailData.latitude_atual},{' '}
                                                {detailData.longitude_atual}
                                            </p>
                                        </div>
                                    )}
                            </div>

                            <div>
                                <h4 className="mb-2 flex items-center gap-2 text-sm font-semibold">
                                    <Users className="size-4 text-primary" />
                                    Membros ({detailData.membros?.length ?? 0})
                                </h4>
                                {detailData.membros &&
                                detailData.membros.length > 0 ? (
                                    <div className="space-y-2">
                                        {detailData.membros.map((m) => (
                                            <div
                                                key={m.id}
                                                className="flex items-center justify-between rounded-lg bg-secondary/50 px-3 py-2"
                                            >
                                                <span className="text-sm font-medium">
                                                    {m.nome}
                                                </span>
                                                <span className="rounded-full border px-2 py-0.5 text-xs text-muted-foreground">
                                                    {funcaoLabel[m.funcao] ??
                                                        m.funcao}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        Nenhum membro vinculado.
                                    </p>
                                )}
                            </div>
                        </div>
                    ) : null}
                </DialogContent>
            </Dialog>

            {/* Dialog criar/editar */}
            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {editingId ? 'Editar Brigada' : 'Nova Brigada'}
                        </DialogTitle>
                        <DialogDescription>
                            {editingId
                                ? 'Altere os campos desejados.'
                                : 'Preencha os dados para criar uma nova brigada.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="brigada-nome">Nome</Label>
                            <Input
                                id="brigada-nome"
                                value={formData.nome}
                                onChange={(e) =>
                                    setFormData((f) => ({
                                        ...f,
                                        nome: e.target.value,
                                    }))
                                }
                                placeholder="Nome da brigada"
                                maxLength={150}
                                aria-invalid={!!formErrors.nome}
                            />
                            {formErrors.nome && (
                                <p className="text-xs text-destructive">
                                    {formErrors.nome[0]}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="brigada-tipo">Tipo</Label>
                            <Input
                                id="brigada-tipo"
                                value={formData.tipo}
                                onChange={(e) =>
                                    setFormData((f) => ({
                                        ...f,
                                        tipo: e.target.value,
                                    }))
                                }
                                placeholder="Ex: florestal, urbano"
                                maxLength={100}
                                aria-invalid={!!formErrors.tipo}
                            />
                            {formErrors.tipo && (
                                <p className="text-xs text-destructive">
                                    {formErrors.tipo[0]}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="brigada-disponivel"
                                checked={formData.disponivel}
                                onCheckedChange={(checked) =>
                                    setFormData((f) => ({
                                        ...f,
                                        disponivel: checked === true,
                                    }))
                                }
                            />
                            <Label htmlFor="brigada-disponivel">
                                Disponível
                            </Label>
                        </div>

                        <div className="space-y-2">
                            <Label className="flex items-center gap-2">
                                <Users className="size-4" />
                                Membros
                                {selectedMemberIds.size > 0 && (
                                    <span className="text-xs font-normal text-muted-foreground">
                                        ({selectedMemberIds.size}{' '}
                                        selecionado
                                        {selectedMemberIds.size !== 1
                                            ? 's'
                                            : ''}
                                        )
                                    </span>
                                )}
                            </Label>

                            {formLoading ? (
                                <div className="space-y-2 py-2">
                                    <div className="h-4 w-3/4 animate-pulse rounded bg-muted" />
                                    <div className="h-4 w-1/2 animate-pulse rounded bg-muted" />
                                </div>
                            ) : (
                                <>
                                    <div className="relative">
                                        <Search className="absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            value={memberSearch}
                                            onChange={(e) =>
                                                setMemberSearch(e.target.value)
                                            }
                                            placeholder="Buscar por nome..."
                                            className="pl-9"
                                        />
                                    </div>

                                    <div className="max-h-48 space-y-1 overflow-y-auto rounded-md border p-2">
                                        {candidateMembros.length === 0 ? (
                                            <p className="py-3 text-center text-xs text-muted-foreground">
                                                {memberSearch
                                                    ? 'Nenhum usuário encontrado.'
                                                    : 'Nenhum usuário disponível.'}
                                            </p>
                                        ) : (
                                            candidateMembros.map((u) => (
                                                (() => {
                                                    const isCurrentlySelected =
                                                        selectedMemberIds.has(
                                                            u.id,
                                                        );
                                                    const isAddBlocked =
                                                        !isCurrentlySelected &&
                                                        u.funcao !==
                                                            'brigadista';
                                                    const tooltipText =
                                                        isAddBlocked &&
                                                        (u.funcao === 'user'
                                                            ? 'usuario precisa ser um brigadista para fazer parte da brigada'
                                                            : 'Gestores não podem adicionar administradores ou gestores na brigada');

                                                    const row = (
                                                        <label
                                                            key={u.id}
                                                            aria-disabled={
                                                                isAddBlocked
                                                            }
                                                            className={cn(
                                                                'flex items-center gap-2.5 rounded-md px-2 py-1.5 transition-colors',
                                                                isAddBlocked
                                                                    ? 'cursor-not-allowed opacity-60'
                                                                    : 'cursor-pointer hover:bg-secondary/60',
                                                            )}
                                                            onClick={(e) => {
                                                                if (
                                                                    isAddBlocked
                                                                ) {
                                                                    e.preventDefault();
                                                                    e.stopPropagation();
                                                                }
                                                            }}
                                                            onKeyDown={(e) => {
                                                                if (
                                                                    isAddBlocked
                                                                ) {
                                                                    e.preventDefault();
                                                                    e.stopPropagation();
                                                                }
                                                            }}
                                                        >
                                                            <Checkbox
                                                                checked={isCurrentlySelected}
                                                                disabled={
                                                                    isAddBlocked
                                                                }
                                                                onCheckedChange={() => {
                                                                    if (
                                                                        isAddBlocked
                                                                    ) {
                                                                        return;
                                                                    }
                                                                    toggleMember(
                                                                        u.id,
                                                                    );
                                                                }}
                                                            />
                                                            <span className="flex-1 truncate text-sm">
                                                                {u.nome}
                                                            </span>
                                                            <span
                                                                className={cn(
                                                                    'shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-medium',
                                                                    funcaoBadge[
                                                                        u.funcao
                                                                    ] ??
                                                                        'border-border text-muted-foreground',
                                                                )}
                                                            >
                                                                {funcaoLabel[
                                                                    u.funcao
                                                                ] ?? u.funcao}
                                                            </span>
                                                        </label>
                                                    );

                                                    if (!isAddBlocked) {
                                                        return row;
                                                    }

                                                    return (
                                                        <Tooltip key={u.id}>
                                                            <TooltipTrigger
                                                                asChild
                                                            >
                                                                {row}
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                {tooltipText}
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    );
                                                })()
                                            ))
                                        )}
                                    </div>
                                </>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setFormOpen(false)}
                            disabled={formSubmitting}
                        >
                            Cancelar
                        </Button>
                        <Button
                            onClick={submitForm}
                            disabled={formSubmitting || formLoading}
                        >
                            {formSubmitting
                                ? 'Salvando...'
                                : editingId
                                  ? 'Salvar'
                                  : 'Criar'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog confirmar exclusão */}
            <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Remover brigada</DialogTitle>
                        <DialogDescription>
                            Tem certeza que deseja remover a brigada{' '}
                            <strong>{deletingBrigada?.nome}</strong>? Esta ação
                            não pode ser desfeita.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleteOpen(false)}
                            disabled={deleteLoading}
                        >
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={confirmDelete}
                            disabled={deleteLoading}
                        >
                            {deleteLoading ? 'Removendo...' : 'Remover'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Dialog de gerenciamento de status do despacho */}
            <Dialog
                open={despachoDetailOpen}
                onOpenChange={(open) => {
                    if (!despachoActionLoading) setDespachoDetailOpen(open);
                }}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Detalhes do Despacho</DialogTitle>
                        <DialogDescription>
                            Gerencie o status deste despacho
                        </DialogDescription>
                    </DialogHeader>

                    {selectedDespacho && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span className="text-muted-foreground">
                                        Brigada
                                    </span>
                                    <p className="font-medium">
                                        {selectedDespacho.brigada_nome}
                                    </p>
                                </div>
                                <div>
                                    <span className="text-muted-foreground">
                                        Incêndio
                                    </span>
                                    <p className="font-medium">
                                        {selectedDespacho.incendio_area_nome}
                                    </p>
                                </div>
                                {selectedDespacho.despachado_em && (
                                    <div>
                                        <span className="text-muted-foreground">
                                            Despachado em
                                        </span>
                                        <p className="font-medium">
                                            {new Date(
                                                selectedDespacho.despachado_em,
                                            ).toLocaleString('pt-BR')}
                                        </p>
                                    </div>
                                )}
                                {selectedDespacho.chegada_em && (
                                    <div>
                                        <span className="text-muted-foreground">
                                            Chegada em
                                        </span>
                                        <p className="font-medium">
                                            {new Date(
                                                selectedDespacho.chegada_em,
                                            ).toLocaleString('pt-BR')}
                                        </p>
                                    </div>
                                )}
                                {selectedDespacho.finalizado_em && (
                                    <div>
                                        <span className="text-muted-foreground">
                                            Finalizado em
                                        </span>
                                        <p className="font-medium">
                                            {new Date(
                                                selectedDespacho.finalizado_em,
                                            ).toLocaleString('pt-BR')}
                                        </p>
                                    </div>
                                )}
                                <div className="col-span-2">
                                    <span className="text-muted-foreground">
                                        Status
                                    </span>
                                    <p>
                                        <span
                                            className={cn(
                                                'inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                                despachoStatus === 'finalizado'
                                                    ? 'bg-resolved/15 text-resolved'
                                                    : despachoStatus ===
                                                        'no_local'
                                                      ? 'bg-contained/15 text-contained'
                                                      : 'bg-warning/15 text-warning',
                                            )}
                                        >
                                            {despachoStatus === 'finalizado'
                                                ? 'Finalizado'
                                                : despachoStatus === 'no_local'
                                                  ? 'No local'
                                                  : 'Em deslocamento'}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            {selectedDespacho.observacoes && (
                                <div className="text-sm">
                                    <span className="text-muted-foreground">
                                        Observações
                                    </span>
                                    <p className="mt-1 rounded-md bg-secondary/50 p-2 text-sm">
                                        {selectedDespacho.observacoes}
                                    </p>
                                </div>
                            )}

                            {despachoStatus === 'em_deslocamento' && (
                                <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/30">
                                    <p className="mb-3 text-sm text-amber-800 dark:text-amber-200">
                                        A brigada está a caminho do incêndio.
                                        Registre a chegada quando a brigada
                                        chegar ao local.
                                    </p>
                                    <Button
                                        onClick={registrarChegada}
                                        disabled={despachoActionLoading}
                                        className="w-full"
                                    >
                                        {despachoActionLoading
                                            ? 'Registrando...'
                                            : 'Registrar Chegada'}
                                    </Button>
                                </div>
                            )}

                            {despachoStatus === 'no_local' && (
                                <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-900 dark:bg-blue-950/30">
                                    <p className="mb-3 text-sm text-blue-800 dark:text-blue-200">
                                        A brigada está no local do incêndio.
                                        Finalize quando a operação for
                                        concluída.
                                    </p>
                                    <div className="mb-3 space-y-2">
                                        <Label
                                            htmlFor="finalizar-obs"
                                            className="text-xs text-blue-700 dark:text-blue-300"
                                        >
                                            Observações (opcional)
                                        </Label>
                                        <Textarea
                                            id="finalizar-obs"
                                            value={finalizarObs}
                                            onChange={(e) =>
                                                setFinalizarObs(e.target.value)
                                            }
                                            placeholder="Ex: Fogo contido, sem vítimas..."
                                            maxLength={1000}
                                            rows={2}
                                        />
                                    </div>
                                    <Button
                                        onClick={finalizarDespacho}
                                        disabled={despachoActionLoading}
                                        className="w-full"
                                    >
                                        {despachoActionLoading
                                            ? 'Finalizando...'
                                            : 'Finalizar Despacho'}
                                    </Button>
                                </div>
                            )}

                            {despachoStatus === 'finalizado' && (
                                <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900 dark:bg-emerald-950/30">
                                    <p className="text-sm text-emerald-800 dark:text-emerald-200">
                                        Este despacho foi finalizado. A brigada
                                        está disponível para novas operações.
                                    </p>
                                </div>
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>

            {/* Dialog de despacho */}
            <Dialog
                open={dispatchOpen}
                onOpenChange={(open) => {
                    if (!dispatchSubmitting) setDispatchOpen(open);
                }}
            >
                <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Send className="size-5 text-primary" />
                            Despachar Brigadas
                        </DialogTitle>
                        <DialogDescription>
                            {dispatchStep === 1
                                ? 'Selecione o incêndio a ser combatido.'
                                : 'Selecione as brigadas para o despacho.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="mb-1 flex items-center gap-2 text-xs text-muted-foreground">
                        <span
                            className={cn(
                                'flex size-5 items-center justify-center rounded-full text-[10px] font-bold',
                                dispatchStep === 1
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground',
                            )}
                        >
                            1
                        </span>
                        <span
                            className={cn(
                                dispatchStep === 1 && 'font-medium text-foreground',
                            )}
                        >
                            Incêndio
                        </span>
                        <ArrowRight className="size-3" />
                        <span
                            className={cn(
                                'flex size-5 items-center justify-center rounded-full text-[10px] font-bold',
                                dispatchStep === 2
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground',
                            )}
                        >
                            2
                        </span>
                        <span
                            className={cn(
                                dispatchStep === 2 && 'font-medium text-foreground',
                            )}
                        >
                            Brigadas
                        </span>
                    </div>

                    {dispatchStep === 1 && (
                        <div className="space-y-3">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={dispatchSearch}
                                    onChange={(e) =>
                                        setDispatchSearch(e.target.value)
                                    }
                                    placeholder="Buscar por área, status..."
                                    className="pl-9"
                                />
                            </div>

                            <div className="max-h-64 space-y-2 overflow-y-auto">
                                {filteredIncendios.length === 0 ? (
                                    <p className="py-6 text-center text-sm text-muted-foreground">
                                        {dispatchSearch
                                            ? 'Nenhum incêndio encontrado.'
                                            : 'Nenhum incêndio ativo ou contido.'}
                                    </p>
                                ) : (
                                    filteredIncendios.map((inc) => (
                                        <label
                                            key={inc.id}
                                            className={cn(
                                                'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors',
                                                selectedIncendioId === inc.id
                                                    ? 'border-primary bg-primary/5'
                                                    : 'hover:bg-secondary/60',
                                            )}
                                        >
                                            <input
                                                type="radio"
                                                name="dispatch-incendio"
                                                className="mt-1 accent-primary"
                                                checked={
                                                    selectedIncendioId ===
                                                    inc.id
                                                }
                                                onChange={() =>
                                                    setSelectedIncendioId(
                                                        inc.id,
                                                    )
                                                }
                                            />
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <Flame className="size-3.5 shrink-0 text-orange-500" />
                                                    <span className="truncate text-sm font-medium">
                                                        {inc.area_nome}
                                                    </span>
                                                </div>
                                                <div className="mt-1 flex flex-wrap items-center gap-1.5">
                                                    <span
                                                        className={cn(
                                                            'rounded-full border px-2 py-0.5 text-[10px] font-medium',
                                                            statusIncendioBadge[
                                                                inc.status
                                                            ] ??
                                                                'border-border text-muted-foreground',
                                                        )}
                                                    >
                                                        {statusIncendioLabel[
                                                            inc.status
                                                        ] ?? inc.status}
                                                    </span>
                                                    <span
                                                        className={cn(
                                                            'rounded-full border px-2 py-0.5 text-[10px] font-medium',
                                                            nivelRiscoBadge[
                                                                inc.nivel_risco
                                                            ] ??
                                                                'border-border text-muted-foreground',
                                                        )}
                                                    >
                                                        {nivelRiscoLabel[
                                                            inc.nivel_risco
                                                        ] ?? inc.nivel_risco}
                                                    </span>
                                                    {inc.detectado_em && (
                                                        <span className="text-[10px] text-muted-foreground">
                                                            {new Date(
                                                                inc.detectado_em,
                                                            ).toLocaleString(
                                                                'pt-BR',
                                                            )}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </label>
                                    ))
                                )}
                            </div>
                        </div>
                    )}

                    {dispatchStep === 2 && (
                        <div className="space-y-3">
                            {selectedIncendio && (
                                <div className="flex items-center gap-2 rounded-lg bg-secondary/50 px-3 py-2 text-sm">
                                    <Flame className="size-3.5 shrink-0 text-orange-500" />
                                    <span className="font-medium">
                                        {selectedIncendio.area_nome}
                                    </span>
                                    <span
                                        className={cn(
                                            'rounded-full border px-2 py-0.5 text-[10px] font-medium',
                                            statusIncendioBadge[
                                                selectedIncendio.status
                                            ] ??
                                                'border-border text-muted-foreground',
                                        )}
                                    >
                                        {statusIncendioLabel[
                                            selectedIncendio.status
                                        ] ?? selectedIncendio.status}
                                    </span>
                                </div>
                            )}

                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={dispatchSearch}
                                    onChange={(e) =>
                                        setDispatchSearch(e.target.value)
                                    }
                                    placeholder="Buscar brigada por nome..."
                                    className="pl-9"
                                />
                            </div>

                            <div className="max-h-64 space-y-1 overflow-y-auto rounded-md border p-2">
                                {filteredDispatchBrigadas.length === 0 ? (
                                    <p className="py-4 text-center text-xs text-muted-foreground">
                                        {dispatchSearch
                                            ? 'Nenhuma brigada encontrada.'
                                            : 'Nenhuma brigada cadastrada.'}
                                    </p>
                                ) : (
                                    filteredDispatchBrigadas.map((b) => {
                                        const isDisabled = !b.disponivel;
                                        return (
                                            <label
                                                key={b.id}
                                                className={cn(
                                                    'flex items-center gap-2.5 rounded-md px-2 py-1.5 transition-colors',
                                                    isDisabled
                                                        ? 'cursor-not-allowed opacity-50'
                                                        : 'cursor-pointer hover:bg-secondary/60',
                                                )}
                                            >
                                                <Checkbox
                                                    checked={selectedBrigadaIds.has(
                                                        b.id,
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggleDispatchBrigada(
                                                            b.id,
                                                        )
                                                    }
                                                    disabled={isDisabled}
                                                />
                                                <span className="flex-1 truncate text-sm">
                                                    {b.nome}
                                                </span>
                                                <div className="flex items-center gap-1.5">
                                                    <span className="text-xs text-muted-foreground">
                                                        {b.usuarios_count ?? 0}{' '}
                                                        membro
                                                        {(b.usuarios_count ??
                                                            0) !== 1
                                                            ? 's'
                                                            : ''}
                                                    </span>
                                                    {isDisabled ? (
                                                        <span className="rounded-full border border-orange-200 bg-orange-100 px-2 py-0.5 text-[10px] font-medium text-orange-700">
                                                            Em operação
                                                        </span>
                                                    ) : (
                                                        <span className="rounded-full border border-emerald-200 bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700">
                                                            Disponível
                                                        </span>
                                                    )}
                                                </div>
                                            </label>
                                        );
                                    })
                                )}
                            </div>

                            {selectedBrigadaIds.size > 0 && (
                                <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <CheckCircle className="size-3 text-primary" />
                                    {selectedBrigadaIds.size} brigada
                                    {selectedBrigadaIds.size !== 1
                                        ? 's'
                                        : ''}{' '}
                                    selecionada
                                    {selectedBrigadaIds.size !== 1 ? 's' : ''}
                                </p>
                            )}
                        </div>
                    )}

                    <DialogFooter className="gap-2 sm:gap-0">
                        {dispatchStep === 2 && (
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setDispatchStep(1);
                                    setDispatchSearch('');
                                }}
                                disabled={dispatchSubmitting}
                                className="mr-auto"
                            >
                                <ArrowLeft className="size-4" />
                                Voltar
                            </Button>
                        )}
                        <Button
                            variant="outline"
                            onClick={() => setDispatchOpen(false)}
                            disabled={dispatchSubmitting}
                        >
                            Cancelar
                        </Button>
                        {dispatchStep === 1 ? (
                            <Button
                                onClick={() => {
                                    setDispatchStep(2);
                                    setDispatchSearch('');
                                }}
                                disabled={!selectedIncendioId}
                            >
                                Próximo
                                <ArrowRight className="size-4" />
                            </Button>
                        ) : (
                            <Button
                                onClick={confirmDispatch}
                                disabled={
                                    selectedBrigadaIds.size === 0 ||
                                    dispatchSubmitting
                                }
                            >
                                {dispatchSubmitting ? (
                                    'Despachando...'
                                ) : (
                                    <>
                                        <Send className="size-4" />
                                        Despachar
                                    </>
                                )}
                            </Button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
