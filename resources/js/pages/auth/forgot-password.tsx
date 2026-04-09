// Components
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <AuthLayout
            title="Esqueceu a senha?"
            description="Digite seu e-mail para receber instruções de recuperação"
            headerIcon="key"
        >
            <Head title="Recuperar senha" />

            {status ? (
                <div className="mb-4 text-center text-sm font-medium text-primary">
                    {status}
                </div>
            ) : null}

            <div className="flex flex-col gap-6">
                <Form {...email.form()}>
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">E-mail</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder="seu@email.com"
                                />

                                <InputError message={errors.email} />
                            </div>

                            <div className="flex items-center justify-start">
                                <Button
                                    className="mt-2 h-10 w-full font-semibold"
                                    disabled={processing}
                                    data-test="email-password-reset-link-button"
                                >
                                    {processing && (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    )}
                                    Enviar instruções
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <p className="text-center text-sm text-neutral-600">
                    Lembrou a senha?{' '}
                    <TextLink
                        href={login()}
                        className="font-medium text-primary decoration-primary/25 hover:decoration-primary"
                    >
                        Voltar ao login
                    </TextLink>
                </p>
            </div>
        </AuthLayout>
    );
}
