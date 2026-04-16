import { Head, router, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Clock,
    MapPin,
    Pencil,
    Plus,
    Search,
    Trash2,
    UserCheck,
    UserX,
    Users,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';
import { toast } from 'sonner';

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
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios-setup';
import { cn } from '@/lib/utils';
import { brigadas as brigadasRoute } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { FuncaoUsuario } from '@/types/auth';

type BrigadaItem = {
    id: string;
    nome: string;
    tipo: string;
    latitude_atual: string | null;
    longitude_atual: string | null;
    disponivel: boolean;
    usuarios_count?: number;
};

type DespachoRecente = {
    id: string;
    brigada_nome: string;
    incendio_area_nome: string;
    despachado_em: string | null;
    chegada_em: string | null;
    finalizado_em: string | null;
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

type PageProps = {
    brigadas: BrigadaItem[];
    despachosRecentes: DespachoRecente[];
    podeGerenciar: boolean;
    funcaoAutenticado: FuncaoUsuario;
    usuariosDisponiveis: UsuarioDisponivel[];
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
        despachosRecentes,
        podeGerenciar,
        usuariosDisponiveis,
    } = usePage<PageProps>().props;

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Brigadas" />
            <div className="space-y-6 p-4 lg:p-6">
                <motion.div
                    initial={{ opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="flex items-start justify-between gap-4"
                >
                    <div>
                        <h1 className="text-2xl font-bold">Brigadas</h1>
                        <p className="text-sm text-muted-foreground">
                            Equipes de combate a incêndio
                        </p>
                    </div>
                    {podeGerenciar && (
                        <Button onClick={openCreate}>
                            <Plus className="size-4" />
                            Nova Brigada
                        </Button>
                    )}
                </motion.div>

                {brigadas.length === 0 ? (
                    <div className="glass-panel rounded-xl p-8 text-center text-muted-foreground">
                        Nenhuma brigada cadastrada.
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        {brigadas.map((brigada, i) => {
                            const disponivel = brigada.disponivel;
                            return (
                                <motion.div
                                    key={brigada.id}
                                    initial={{ opacity: 0, y: 10 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: i * 0.07 }}
                                    className="glass-panel cursor-pointer rounded-xl p-5 transition-shadow hover:shadow-md"
                                    onClick={() => openDetail(brigada.id)}
                                    role="button"
                                    tabIndex={0}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' || e.key === ' ')
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
                                                {brigada.usuarios_count ?? 0}{' '}
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
                                                    onClick={(e) =>
                                                        openEdit(e, brigada)
                                                    }
                                                    title="Editar brigada"
                                                >
                                                    <Pencil className="size-3.5" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-destructive hover:text-destructive"
                                                    onClick={(e) =>
                                                        openDelete(e, brigada)
                                                    }
                                                    title="Remover brigada"
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
                    {despachosRecentes.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Nenhum despacho recente.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {despachosRecentes.map((d) => (
                                <div
                                    key={d.id}
                                    className="rounded-lg bg-secondary/50 p-3"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <span className="text-sm font-medium">
                                                {d.brigada_nome}
                                            </span>
                                            <span className="ml-2 text-xs text-muted-foreground">
                                                → {d.incendio_area_nome}
                                            </span>
                                        </div>
                                        <span
                                            className={cn(
                                                'rounded-full px-2 py-0.5 text-xs font-semibold',
                                                d.finalizado_em
                                                    ? 'bg-resolved/15 text-resolved'
                                                    : d.chegada_em
                                                      ? 'bg-contained/15 text-contained'
                                                      : 'bg-warning/15 text-warning',
                                            )}
                                        >
                                            {d.finalizado_em
                                                ? 'Finalizado'
                                                : d.chegada_em
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
                                                <label
                                                    key={u.id}
                                                    className="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-1.5 transition-colors hover:bg-secondary/60"
                                                >
                                                    <Checkbox
                                                        checked={selectedMemberIds.has(
                                                            u.id,
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleMember(u.id)
                                                        }
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
        </AppLayout>
    );
}
