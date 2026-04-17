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

export type OrigemTabelaAlerta =
    | 'incendios'
    | 'leituras_meteorologicas'
    | 'deteccoes_satelite'
    | string;

export type OrigemResumoAlerta =
    | {
          tipo: 'incendio';
          incendio_id: string;
          area_nome: string | null;
          detectado_em: string | null;
          status: string;
          local_critico_nome: string | null;
      }
    | {
          tipo: 'leitura_meteorologica';
          leitura_id: string;
          incendio_id: string;
          area_nome: string | null;
          temperatura: string;
          umidade: string;
          registrado_em: string | null;
      }
    | {
          tipo: 'deteccao_satelite';
          deteccao_id: string;
          fonte: string | null;
          latitude: string;
          longitude: string;
          confianca: string;
          detectado_em: string | null;
      };

export interface AlertaApiItem {
    id: string;
    tipo: TipoAlerta;
    mensagem: string;
    origem_id: string;
    origem_tabela: OrigemTabelaAlerta;
    origem_label: string;
    origem_resumo?: OrigemResumoAlerta | null;
    enviado_em: string;
    entregue: boolean;
}

export interface PaginatedAlertas {
    data: AlertaApiItem[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
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

export type FuncaoUsuarioMock =
    | 'user'
    | 'brigadista'
    | 'gestor'
    | 'administrador';

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
