export type FuncaoUsuario = 'user' | 'brigadista' | 'gestor' | 'administrador';

export type User = {
    id: string;
    nome: string;
    email: string;
    cpf: string;
    funcao?: FuncaoUsuario;
    avatar?: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
