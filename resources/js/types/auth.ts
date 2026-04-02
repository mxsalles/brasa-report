export type User = {
    id: string;
    nome: string;
    email: string;
    cpf: string;
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
