import { Head } from '@inertiajs/react';
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
import { cn } from '@/lib/utils';
import {
    mockLogsAuditoria,
    mockUsuariosAdmin,
} from '@/data/operacoes-mock';
import { administracao as administracaoRoute } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { FuncaoUsuarioMock } from '@/types/operacoes';

const funcaoLabel: Record<FuncaoUsuarioMock, string> = {
    admin: 'Administrador',
    gestor: 'Gestor',
    brigadista: 'Brigadista',
};

const funcaoBadge: Record<FuncaoUsuarioMock, string> = {
    admin: 'border-purple-200 bg-purple-100 text-purple-700',
    gestor: 'border-blue-200 bg-blue-100 text-blue-700',
    brigadista: 'border-emerald-200 bg-emerald-100 text-emerald-700',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administração', href: administracaoRoute().url },
];

export default function Administracao() {
    const [usuarios, setUsuarios] = useState(mockUsuariosAdmin);
    const [busca, setBusca] = useState('');
    const [buscaLog, setBuscaLog] = useState('');
    const [aba, setAba] = useState<'usuarios' | 'logs'>('usuarios');

    const usuariosFiltrados = usuarios.filter(
        (u) =>
            u.nome.toLowerCase().includes(busca.toLowerCase()) ||
            u.email.toLowerCase().includes(busca.toLowerCase()),
    );

    const logsFiltrados = mockLogsAuditoria.filter(
        (l) =>
            l.descricao.toLowerCase().includes(buscaLog.toLowerCase()) ||
            l.usuario_nome.toLowerCase().includes(buscaLog.toLowerCase()),
    );

    const alterarFuncao = (userId: string, novaFuncao: FuncaoUsuarioMock) => {
        setUsuarios((prev) =>
            prev.map((u) =>
                u.id === userId ? { ...u, funcao: novaFuncao } : u,
            ),
        );
    };

    const toggleBloqueio = (userId: string) => {
        setUsuarios((prev) =>
            prev.map((u) =>
                u.id === userId ? { ...u, bloqueado: !u.bloqueado } : u,
            ),
        );
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
                        (mock)
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
                                {usuariosFiltrados.length} usuário(s)
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
                                            onValueChange={(val) =>
                                                alterarFuncao(
                                                    user.id,
                                                    val as FuncaoUsuarioMock,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="h-8 w-[140px] text-xs">
                                                <SelectValue placeholder="Alterar função" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="brigadista">
                                                    Brigadista
                                                </SelectItem>
                                                <SelectItem value="gestor">
                                                    Gestor
                                                </SelectItem>
                                                <SelectItem value="admin">
                                                    Administrador
                                                </SelectItem>
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
                                            onClick={() =>
                                                toggleBloqueio(user.id)
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
                                                Descrição
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
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {log.descricao}
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
                                        <p className="text-xs text-muted-foreground">
                                            {log.descricao}
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
