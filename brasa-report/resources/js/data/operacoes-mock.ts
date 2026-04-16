import type {
    AlertaDetalhe,
    BrigadaResumo,
    DespachoBrigadaResumo,
    IncendioMapa,
    LogAuditoriaResumo,
    UsuarioAdminResumo,
} from '@/types/operacoes';

export const mockIncendiosMapa: IncendioMapa[] = [
    {
        id: 'i1',
        latitude: -18.05,
        longitude: -57.45,
        status: 'ativo',
        nivel_risco: 'alto',
        descricao:
            'Incêndio de grandes proporções próximo à comunidade ribeirinha',
        area_nome: 'Serra do Amolar Norte',
        registrado_por: 'Carlos Silva',
        criado_em: '2026-04-06T10:30:00Z',
        tipo_local_critico: 'residencia',
    },
    {
        id: 'i2',
        latitude: -18.12,
        longitude: -57.52,
        status: 'contido',
        nivel_risco: 'medio',
        descricao: 'Foco contido após ação rápida da brigada Bravo',
        area_nome: 'Serra do Amolar Sul',
        registrado_por: 'Ana Martins',
        criado_em: '2026-04-05T14:15:00Z',
        tipo_local_critico: null,
    },
    {
        id: 'i3',
        latitude: -17.98,
        longitude: -57.38,
        status: 'ativo',
        nivel_risco: 'alto',
        descricao:
            'Foco detectado via satélite próximo a depósito de combustível',
        area_nome: 'Rio Paraguai',
        registrado_por: 'Sistema FIRMS',
        criado_em: '2026-04-06T08:45:00Z',
        tipo_local_critico: 'infraestrutura',
    },
    {
        id: 'i4',
        latitude: -18.2,
        longitude: -57.6,
        status: 'resolvido',
        nivel_risco: 'baixo',
        descricao: 'Queimada controlada encerrada',
        area_nome: 'Pantanal Central',
        registrado_por: 'João Pereira',
        criado_em: '2026-04-04T09:00:00Z',
        tipo_local_critico: null,
    },
];

export const mockAlertasDetalhados: AlertaDetalhe[] = [
    {
        id: 'a1',
        tipo: 'fogo_detectado',
        mensagem: 'Novo foco de incêndio detectado via FIRMS',
        criado_em: '2026-04-06T08:45:00Z',
        lido: false,
    },
    {
        id: 'a2',
        tipo: 'temperatura_alta',
        mensagem: 'Temperatura acima de 42°C na Serra do Amolar',
        criado_em: '2026-04-06T07:00:00Z',
        lido: false,
    },
    {
        id: 'a3',
        tipo: 'umidade_baixa',
        mensagem: 'Umidade relativa abaixo de 15% — risco extremo',
        criado_em: '2026-04-06T06:30:00Z',
        lido: true,
    },
    {
        id: 'a4',
        tipo: 'proximidade_local_critico',
        mensagem: 'Incêndio a menos de 500m de escola municipal',
        criado_em: '2026-04-05T16:20:00Z',
        lido: true,
    },
];

export const mockBrigadas: BrigadaResumo[] = [
    {
        id: 'b1',
        nome: 'Brigada Alpha',
        regiao: 'Serra do Amolar Norte',
        membros: 8,
        status: 'disponivel',
    },
    {
        id: 'b2',
        nome: 'Brigada Bravo',
        regiao: 'Serra do Amolar Sul',
        membros: 6,
        status: 'em_campo',
    },
    {
        id: 'b3',
        nome: 'Brigada Charlie',
        regiao: 'Rio Paraguai',
        membros: 10,
        status: 'disponivel',
    },
    {
        id: 'b4',
        nome: 'Brigada Delta',
        regiao: 'Pantanal Central',
        membros: 7,
        status: 'indisponivel',
    },
];

export const mockDespachos: DespachoBrigadaResumo[] = [
    {
        id: 'd1',
        incendio_id: 'i1',
        brigada_nome: 'Brigada Alpha',
        despachado_em: '2026-04-06T10:35:00Z',
        chegada_em: null,
        finalizado_em: null,
    },
    {
        id: 'd2',
        incendio_id: 'i2',
        brigada_nome: 'Brigada Bravo',
        despachado_em: '2026-04-05T14:20:00Z',
        chegada_em: '2026-04-05T15:00:00Z',
        finalizado_em: '2026-04-05T18:30:00Z',
    },
    {
        id: 'd3',
        incendio_id: 'i3',
        brigada_nome: 'Brigada Bravo',
        despachado_em: '2026-04-06T09:00:00Z',
        chegada_em: null,
        finalizado_em: null,
    },
];

export const mockUsuariosAdmin: UsuarioAdminResumo[] = [
    {
        id: 'u1',
        nome: 'Carlos Silva',
        email: 'carlos@caninde.ms',
        funcao: 'administrador',
        brigada_nome: null,
        bloqueado: false,
        criado_em: '2025-11-10T08:00:00Z',
    },
    {
        id: 'u2',
        nome: 'Ana Martins',
        email: 'ana@caninde.ms',
        funcao: 'gestor',
        brigada_nome: null,
        bloqueado: false,
        criado_em: '2025-12-01T09:00:00Z',
    },
    {
        id: 'u3',
        nome: 'João Pereira',
        email: 'joao@caninde.ms',
        funcao: 'brigadista',
        brigada_nome: 'Brigada Alpha',
        bloqueado: false,
        criado_em: '2026-01-15T10:00:00Z',
    },
    {
        id: 'u4',
        nome: 'Maria Souza',
        email: 'maria@caninde.ms',
        funcao: 'brigadista',
        brigada_nome: 'Brigada Bravo',
        bloqueado: false,
        criado_em: '2026-02-20T11:00:00Z',
    },
    {
        id: 'u5',
        nome: 'Pedro Lima',
        email: 'pedro@caninde.ms',
        funcao: 'brigadista',
        brigada_nome: 'Brigada Charlie',
        bloqueado: true,
        criado_em: '2026-03-05T14:00:00Z',
    },
    {
        id: 'u6',
        nome: 'Fernanda Costa',
        email: 'fernanda@caninde.ms',
        funcao: 'gestor',
        brigada_nome: null,
        bloqueado: false,
        criado_em: '2026-03-28T16:00:00Z',
    },
];

export const mockLogsAuditoria: LogAuditoriaResumo[] = [
    {
        id: 'l1',
        usuario_nome: 'Carlos Silva',
        acao: 'LOGIN',
        descricao: 'Login realizado com sucesso',
        criado_em: '2026-04-06T10:00:00Z',
    },
    {
        id: 'l2',
        usuario_nome: 'Ana Martins',
        acao: 'CADASTRO',
        descricao: 'Novo usuário cadastrado: Pedro Lima',
        criado_em: '2026-03-05T14:00:00Z',
    },
    {
        id: 'l3',
        usuario_nome: 'Carlos Silva',
        acao: 'BLOQUEIO',
        descricao: 'Usuário Pedro Lima bloqueado por inatividade',
        criado_em: '2026-04-01T09:30:00Z',
    },
    {
        id: 'l4',
        usuario_nome: 'João Pereira',
        acao: 'INCENDIO',
        descricao: 'Registrou incêndio na Serra do Amolar Norte',
        criado_em: '2026-04-06T10:30:00Z',
    },
    {
        id: 'l5',
        usuario_nome: 'Ana Martins',
        acao: 'DESPACHO',
        descricao: 'Despachou Brigada Alpha para Serra do Amolar Norte',
        criado_em: '2026-04-06T10:35:00Z',
    },
];

export const mockClimaMapa = {
    temperatura: 38,
    umidade: 22,
    ventoKmh: 18,
    previsaoChuva: 'Sem previsão de chuva nas próximas 48h',
    condicao: 'Seco e quente',
};
