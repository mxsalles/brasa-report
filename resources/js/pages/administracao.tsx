import { Head, router, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Ban,
    CheckCircle,
    ScrollText,
    Search,
    Shield,
    UserCog,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios-setup';
import { cn } from '@/lib/utils';
import { administracao as administracaoRoute } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { FuncaoUsuario } from '@/types/auth';

type UsuarioAdminLinha = {
    id: string;
    nome: string;
    email: string;
    funcao: FuncaoUsuario;
    brigada_id: string | null;
    brigada_nome?: string | null;
    bloqueado: boolean;
    criado_em: string;
};

type LogLinha = {
    id: string;
    criado_em: string;
    usuario_nome: string;
    acao: string;
    detalhes: string | null;
};

type Paginated<T> = {
    data: T[];
    links: unknown;
    meta: { total?: number; per_page?: number; current_page?: number };
};

type PageProps = {
    usuarios: Paginated<UsuarioAdminLinha>;
    logsAuditoria: Paginated<LogLinha>;
    podeGerenciarAdministradores: boolean;
    funcaoAutenticado: FuncaoUsuario;
};

const funcaoLabel: Record<FuncaoUsuario, string> = {
    user: 'Usuário',
    brigadista: 'Brigadista',
    gestor: 'Gestor',
    administrador: 'Administrador',
};

const funcaoBadge: Record<FuncaoUsuario, string> = {
    user: 'border-slate-200 bg-slate-100 text-slate-700',
    administrador: 'border-purple-200 bg-purple-100 text-purple-700',
    gestor: 'border-blue-200 bg-blue-100 text-blue-700',
    brigadista: 'border-emerald-200 bg-emerald-100 text-emerald-700',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administração', href: administracaoRoute().url },
];

function funcoesSelecionaveis(
    podeGerenciarAdministradores: boolean,
): FuncaoUsuario[] {
    if (podeGerenciarAdministradores) {
        return ['user', 'brigadista', 'gestor', 'administrador'];
    }

    return ['user', 'brigadista'];
}

export default function Administracao() {
    const {
        usuarios,
        logsAuditoria,
        podeGerenciarAdministradores,
        funcaoAutenticado,
    } = usePage<PageProps>().props;

    const [busca, setBusca] = useState('');
    const [buscaLog, setBuscaLog] = useState('');
    const [aba, setAba] = useState<'usuarios' | 'logs'>('usuarios');
    const [carregandoId, setCarregandoId] = useState<string | null>(null);

    const opcoesFuncao = funcoesSelecionaveis(podeGerenciarAdministradores);

    const usuariosFiltrados = usuarios.data.filter(
        (u) =>
            u.nome.toLowerCase().includes(busca.toLowerCase()) ||
            u.email.toLowerCase().includes(busca.toLowerCase()),
    );

    const logsFiltrados = logsAuditoria.data.filter(
        (l) =>
            l.acao.toLowerCase().includes(buscaLog.toLowerCase()) ||
            l.usuario_nome.toLowerCase().includes(buscaLog.toLowerCase()) ||
            (l.detalhes?.toLowerCase().includes(buscaLog.toLowerCase()) ??
                false),
    );

    const alterarFuncao = async (userId: string, novaFuncao: FuncaoUsuario) => {
        setCarregandoId(userId);
        try {
            await axios.patch(`/api/usuarios/${userId}/funcao`, {
                funcao: novaFuncao,
            });
            toast.success('Função atualizada.');
            router.reload({ only: ['usuarios', 'logsAuditoria'] });
        } catch (err: unknown) {
            const msg =
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
                    ? err.response.data.message
                    : 'Não foi possível alterar a função.';
            toast.error(msg);
        } finally {
            setCarregandoId(null);
        }
    };

    const alternarBloqueio = async (userId: string) => {
        setCarregandoId(userId);
        try {
            await axios.patch(`/api/usuarios/${userId}/bloqueio`);
            toast.success('Estado de bloqueio atualizado.');
            router.reload({ only: ['usuarios', 'logsAuditoria'] });
        } catch (err: unknown) {
            const msg =
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
                    ? err.response.data.message
                    : 'Não foi possível alterar o bloqueio.';
            toast.error(msg);
        } finally {
            setCarregandoId(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Administração" />
            <div className="space-y-6 p-4 lg:p-6">
                <motion.div
                    initial={{ opacity: 0, y: -10 }}
                    animate={{ opacity: 1, y: 0 }}
                >
                    <h1 className="flex items-center gap-2 text-2xl font-bold">
                        <Shield className="size-6 text-primary" />
                        Administração
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Gerenciamento de usuários, funções e logs de auditoria
                        {funcaoAutenticado === 'gestor'
                            ? ' (como gestor, apenas funções usuário e brigadista)'
                            : ''}
                    </p>
                </motion.div>

                <div className="grid w-full max-w-md grid-cols-2 gap-2 rounded-lg border border-border bg-muted/40 p-1">
                    <button
                        type="button"
                        onClick={() => setAba('usuarios')}
                        className={cn(
                            'flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            aba === 'usuarios'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        <Users className="size-4" />
                        Usuários
                    </button>
                    <button
                        type="button"
                        onClick={() => setAba('logs')}
                        className={cn(
                            'flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            aba === 'logs'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        <ScrollText className="size-4" />
                        Logs de Auditoria
                    </button>
                </div>

                {aba === 'usuarios' ? (
                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center gap-3">
                            <div className="relative max-w-sm flex-1">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Buscar por nome ou e-mail..."
                                    value={busca}
                                    onChange={(e) => setBusca(e.target.value)}
                                    className="pl-9"
                                />
                            </div>
                            <span className="text-sm text-muted-foreground">
                                {usuariosFiltrados.length} usuário(s) (página{' '}
                                {usuarios.meta.current_page ?? 1} de{' '}
                                {Math.max(
                                    1,
                                    Math.ceil(
                                        (usuarios.meta.total ?? 0) /
                                            (usuarios.meta.per_page ?? 20),
                                    ),
                                )}
                                )
                            </span>
                        </div>

                        <div className="space-y-3">
                            {usuariosFiltrados.map((user) => (
                                <motion.div
                                    key={user.id}
                                    initial={{ opacity: 0, y: 5 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    className={cn(
                                        'glass-panel flex flex-col gap-4 rounded-xl p-4 sm:flex-row sm:items-center',
                                        user.bloqueado && 'opacity-60',
                                    )}
                                >
                                    <div className="flex min-w-0 flex-1 items-center gap-3">
                                        <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                            <UserCog className="size-5 text-primary" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-semibold">
                                                {user.nome}
                                                {user.bloqueado ? (
                                                    <span className="ml-2 text-xs font-normal text-destructive">
                                                        (bloqueado)
                                                    </span>
                                                ) : null}
                                            </p>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {user.email}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Cadastro:{' '}
                                                {new Date(
                                                    user.criado_em,
                                                ).toLocaleDateString('pt-BR')}
                                                {user.brigada_nome
                                                    ? ` · Brigada: ${user.brigada_nome}`
                                                    : ''}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-2">
                                        <span
                                            className={cn(
                                                'rounded-full border px-2.5 py-1 text-xs font-medium',
                                                funcaoBadge[user.funcao],
                                            )}
                                        >
                                            {funcaoLabel[user.funcao]}
                                        </span>

                                        <Select
                                            value={user.funcao}
                                            disabled={
                                                carregandoId === user.id ||
                                                (!podeGerenciarAdministradores &&
                                                    (user.funcao ===
                                                        'gestor' ||
                                                        user.funcao ===
                                                            'administrador'))
                                            }
                                            onValueChange={(val) =>
                                                alterarFuncao(
                                                    user.id,
                                                    val as FuncaoUsuario,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="h-8 w-[160px] text-xs">
                                                <SelectValue placeholder="Alterar função" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opcoesFuncao.map((f) => (
                                                    <SelectItem key={f} value={f}>
                                                        {funcaoLabel[f]}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>

                                        <Button
                                            variant={
                                                user.bloqueado
                                                    ? 'outline'
                                                    : 'destructive'
                                            }
                                            size="sm"
                                            className="h-8 text-xs"
                                            disabled={
                                                carregandoId === user.id ||
                                                (!podeGerenciarAdministradores &&
                                                    (user.funcao ===
                                                        'gestor' ||
                                                        user.funcao ===
                                                            'administrador'))
                                            }
                                            onClick={() =>
                                                alternarBloqueio(user.id)
                                            }
                                        >
                                            {user.bloqueado ? (
                                                <>
                                                    <CheckCircle className="mr-1 size-3.5" />
                                                    Desbloquear
                                                </>
                                            ) : (
                                                <>
                                                    <Ban className="mr-1 size-3.5" />
                                                    Bloquear
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center gap-3">
                            <div className="relative max-w-sm flex-1">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Buscar nos logs..."
                                    value={buscaLog}
                                    onChange={(e) =>
                                        setBuscaLog(e.target.value)
                                    }
                                    className="pl-9"
                                />
                            </div>
                            <span className="text-sm text-muted-foreground">
                                {logsFiltrados.length} registro(s)
                            </span>
                        </div>

                        <div className="glass-panel overflow-hidden rounded-xl">
                            <div className="hidden overflow-x-auto md:block">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border bg-secondary/50">
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                                Data/Hora
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                                Usuário
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                                Ação
                                            </th>
                                            <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                                Detalhes
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {logsFiltrados.map((log) => (
                                            <tr
                                                key={log.id}
                                                className="border-b border-border/50 transition-colors hover:bg-secondary/30"
                                            >
                                                <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                                    {new Date(
                                                        log.criado_em,
                                                    ).toLocaleString('pt-BR')}
                                                </td>
                                                <td className="px-4 py-3 font-medium">
                                                    {log.usuario_nome}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <span className="rounded bg-secondary px-2 py-0.5 text-xs font-medium text-foreground">
                                                        {log.acao}
                                                    </span>
                                                </td>
                                                <td className="max-w-md truncate px-4 py-3 text-muted-foreground">
                                                    {log.detalhes ?? '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="divide-y divide-border md:hidden">
                                {logsFiltrados.map((log) => (
                                    <div key={log.id} className="space-y-1 p-4">
                                        <div className="flex items-center justify-between">
                                            <span className="rounded bg-secondary px-2 py-0.5 text-xs font-medium">
                                                {log.acao}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {new Date(
                                                    log.criado_em,
                                                ).toLocaleString('pt-BR')}
                                            </span>
                                        </div>
                                        <p className="text-sm font-medium">
                                            {log.usuario_nome}
                                        </p>
                                        <p className="text-xs break-all text-muted-foreground">
                                            {log.detalhes ?? '—'}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
