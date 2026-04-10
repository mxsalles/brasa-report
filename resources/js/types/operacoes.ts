import type { NivelRisco, StatusIncendio } from '@/types/dashboard';

export type TipoAlerta =
    | 'fogo_detectado'
    | 'temperatura_alta'
    | 'umidade_baixa'
    | 'proximidade_local_critico';

export type TipoLocalCritico = 'residencia' | 'escola' | 'infraestrutura';

export interface IncendioMapa {
    id: string;
    latitude: number;
    longitude: number;
    status: StatusIncendio;
    nivel_risco: NivelRisco;
    descricao: string;
    area_nome: string;
    registrado_por: string;
    criado_em: string;
    tipo_local_critico: TipoLocalCritico | null;
}

export interface AlertaDetalhe {
    id: string;
    tipo: TipoAlerta;
    mensagem: string;
    criado_em: string;
    lido: boolean;
}

export type StatusBrigada = 'disponivel' | 'em_campo' | 'indisponivel';

export interface BrigadaResumo {
    id: string;
    nome: string;
    regiao: string;
    membros: number;
    status: StatusBrigada;
}

export interface DespachoBrigadaResumo {
    id: string;
    incendio_id: string;
    brigada_nome: string;
    despachado_em: string;
    chegada_em: string | null;
    finalizado_em: string | null;
}

export type FuncaoUsuarioMock = 'admin' | 'gestor' | 'brigadista';

export interface UsuarioAdminResumo {
    id: string;
    nome: string;
    email: string;
    funcao: FuncaoUsuarioMock;
    brigada_nome: string | null;
    bloqueado: boolean;
    criado_em: string;
}

export interface LogAuditoriaResumo {
    id: string;
    usuario_nome: string;
    acao: string;
    descricao: string;
    criado_em: string;
}
