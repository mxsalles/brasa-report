import type {
    AlertaResumo,
    EstatisticasDashboard,
    IncendioResumo,
} from '@/types/dashboard';

export const mockIncendios: IncendioResumo[] = [
    {
        id: 'i1',
        status: 'ativo',
        nivel_risco: 'alto',
        descricao:
            'Incêndio de grandes proporções próximo à comunidade ribeirinha',
        area_nome: 'Serra do Amolar Norte',
        registrado_por: 'Carlos Silva',
        criado_em: '2026-04-06T10:30:00Z',
    },
    {
        id: 'i2',
        status: 'contido',
        nivel_risco: 'medio',
        descricao: 'Foco contido após ação rápida da brigada Bravo',
        area_nome: 'Serra do Amolar Sul',
        registrado_por: 'Ana Martins',
        criado_em: '2026-04-05T14:15:00Z',
    },
    {
        id: 'i3',
        status: 'ativo',
        nivel_risco: 'alto',
        descricao:
            'Foco detectado via satélite próximo a depósito de combustível',
        area_nome: 'Rio Paraguai',
        registrado_por: 'Sistema FIRMS',
        criado_em: '2026-04-06T08:45:00Z',
    },
    {
        id: 'i4',
        status: 'resolvido',
        nivel_risco: 'baixo',
        descricao: 'Queimada controlada encerrada',
        area_nome: 'Pantanal Central',
        registrado_por: 'João Pereira',
        criado_em: '2026-04-04T09:00:00Z',
    },
];

export const mockAlertas: AlertaResumo[] = [
    {
        id: 'a1',
        mensagem: 'Novo foco de incêndio detectado via FIRMS',
        criado_em: '2026-04-06T08:45:00Z',
        lido: false,
    },
    {
        id: 'a2',
        mensagem: 'Temperatura acima de 42°C na Serra do Amolar',
        criado_em: '2026-04-06T07:00:00Z',
        lido: false,
    },
    {
        id: 'a3',
        mensagem: 'Umidade relativa abaixo de 15% — risco extremo',
        criado_em: '2026-04-06T06:30:00Z',
        lido: true,
    },
    {
        id: 'a4',
        mensagem: 'Incêndio a menos de 500m de escola municipal',
        criado_em: '2026-04-05T16:20:00Z',
        lido: true,
    },
];

export const mockEstatisticas: EstatisticasDashboard = {
    incendios_ativos: 2,
    incendios_contidos: 1,
    incendios_resolvidos: 1,
    alertas_nao_lidos: 2,
    brigadas_disponiveis: 2,
    brigadas_em_campo: 1,
    temperatura_media: 38,
    umidade_media: 22,
};
