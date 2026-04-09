export type StatusIncendio = 'ativo' | 'contido' | 'resolvido';

export type NivelRisco = 'alto' | 'medio' | 'baixo';

export interface IncendioResumo {
    id: string;
    status: StatusIncendio;
    nivel_risco: NivelRisco;
    descricao: string;
    area_nome: string;
    registrado_por: string;
    criado_em: string;
}

export interface AlertaResumo {
    id: string;
    mensagem: string;
    criado_em: string;
    lido: boolean;
}

export interface EstatisticasDashboard {
    incendios_ativos: number;
    incendios_contidos: number;
    incendios_resolvidos: number;
    alertas_nao_lidos: number;
    brigadas_disponiveis: number;
    brigadas_em_campo: number;
    temperatura_media: number;
    umidade_media: number;
}
