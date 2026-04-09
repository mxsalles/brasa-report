import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { store } from '@/routes/register';

function formatCpfDisplay(raw: string): string {
    const digits = raw.replace(/\D/g, '').slice(0, 11);
    if (digits.length <= 3) {
        return digits;
    }
    if (digits.length <= 6) {
        return `${digits.slice(0, 3)}.${digits.slice(3)}`;
    }
    if (digits.length <= 9) {
        return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;
    }
    return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
}

export default function Register() {
    const [cpfDisplay, setCpfDisplay] = useState('');
    const cpfDigits = cpfDisplay.replace(/\D/g, '').slice(0, 11);

    return (
        <AuthLayout
            title="Criar Conta"
            description="Preencha os dados abaixo para criar sua conta"
            headerIcon="user"
        >
            <Head title="Cadastro" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="cpf" value={cpfDigits} />
                        <div className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nome completo</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Digite seu nome completo"
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="cpf_display">CPF</Label>
                                <Input
                                    id="cpf_display"
                                    type="text"
                                    inputMode="numeric"
                                    autoComplete="off"
                                    tabIndex={2}
                                    value={cpfDisplay}
                                    onChange={(e) =>
                                        setCpfDisplay(
                                            formatCpfDisplay(e.target.value),
                                        )
                                    }
                                    placeholder="000.000.000-00"
                                    aria-invalid={
                                        errors.cpf ? true : undefined
                                    }
                                />
                                <InputError message={errors.cpf} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">E-mail</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={3}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="seu@email.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Senha</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Escolha uma senha segura"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirmar senha
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    required
                                    tabIndex={5}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Digite a senha novamente"
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-1 h-10 w-full font-semibold"
                                tabIndex={6}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Cadastrar
                            </Button>
                        </div>

                        <p className="text-center text-sm text-neutral-600">
                            Já tem uma conta?{' '}
                            <TextLink
                                href={login()}
                                className="font-medium text-primary decoration-primary/25 hover:decoration-primary"
                                tabIndex={7}
                            >
                                Fazer login
                            </TextLink>
                        </p>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
