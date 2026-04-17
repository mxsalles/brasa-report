import { Form, Head } from '@inertiajs/react';
import { Eye, EyeOff, LogIn } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBrasaSplitLayout from '@/layouts/auth/auth-brasa-split-layout';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
};

export default function Login({
    status,
    canResetPassword,
    canRegister,
}: Props) {
    const [mostrarSenha, setMostrarSenha] = useState(false);

    return (
        <AuthBrasaSplitLayout>
            <Head title="Entrar" />

            <div className="glass-panel rounded-2xl p-8">
                <div className="mb-6">
                    <h2 className="text-xl font-bold">Entrar no sistema</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Acesse sua conta de brigadista ou gestor
                    </p>
                </div>

                <Form
                    {...store.form()}
                    resetOnSuccess={['password']}
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="email">E-mail ou CPF</Label>
                                    <Input
                                        id="email"
                                        type="text"
                                        name="email"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="username"
                                        placeholder="Digite seu e-mail ou CPF"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <div className="flex items-center justify-between">
                                        <Label htmlFor="password">Senha</Label>
                                        {canResetPassword ? (
                                            <TextLink
                                                href={request()}
                                                className="text-xs font-medium text-primary decoration-primary/25 hover:decoration-primary"
                                                tabIndex={3}
                                            >
                                                Esqueceu a senha?
                                            </TextLink>
                                        ) : null}
                                    </div>
                                    <div className="relative">
                                        <Input
                                            id="password"
                                            type={
                                                mostrarSenha
                                                    ? 'text'
                                                    : 'password'
                                            }
                                            name="password"
                                            required
                                            tabIndex={2}
                                            autoComplete="current-password"
                                            placeholder="••••••••"
                                            className="pr-10"
                                        />
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setMostrarSenha(!mostrarSenha)
                                            }
                                            className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
                                            tabIndex={-1}
                                            aria-label={
                                                mostrarSenha
                                                    ? 'Ocultar senha'
                                                    : 'Mostrar senha'
                                            }
                                        >
                                            {mostrarSenha ? (
                                                <EyeOff className="size-4" />
                                            ) : (
                                                <Eye className="size-4" />
                                            )}
                                        </button>
                                    </div>
                                    <InputError message={errors.password} />
                                </div>

                                <Button
                                    type="submit"
                                    className="mt-1 h-10 w-full font-semibold"
                                    tabIndex={4}
                                    disabled={processing}
                                    data-test="login-button"
                                >
                                    {processing ? (
                                        <span className="flex items-center justify-center gap-2">
                                            <Spinner />
                                            Entrando…
                                        </span>
                                    ) : (
                                        <span className="flex items-center justify-center gap-2">
                                            <LogIn className="size-4" />
                                            Entrar
                                        </span>
                                    )}
                                </Button>
                            </div>

                            {canRegister ? (
                                <div className="text-center text-sm text-muted-foreground">
                                    Não possui conta?{' '}
                                    <TextLink
                                        href={register()}
                                        className="font-medium text-primary decoration-primary/25 hover:decoration-primary"
                                        tabIndex={5}
                                    >
                                        Realizar cadastro
                                    </TextLink>
                                </div>
                            ) : null}
                        </>
                    )}
                </Form>

                {status ? (
                    <div className="mt-4 text-center text-sm font-medium text-primary">
                        {status}
                    </div>
                ) : null}
            </div>
        </AuthBrasaSplitLayout>
    );
}
