import { motion } from 'framer-motion';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

const LOGO_SRC = '/images/logo-brasa.png';

type AuthBrasaSplitLayoutProps = {
    children: ReactNode;
};

export default function AuthBrasaSplitLayout({
    children,
}: AuthBrasaSplitLayoutProps) {
    return (
        <div
            className={cn(
                'auth-brasa flex min-h-svh flex-col bg-background lg:flex-row',
            )}
        >
            <div className="relative hidden overflow-hidden lg:flex lg:w-1/2 lg:items-center lg:justify-center">
                <div className="absolute inset-0 bg-linear-to-br from-primary/5 via-background to-accent/5" />
                <div className="absolute inset-0 opacity-10">
                    <div className="absolute top-1/4 left-1/4 size-96 rounded-full bg-primary blur-[120px]" />
                    <div className="absolute right-1/4 bottom-1/4 size-64 rounded-full bg-accent blur-[100px]" />
                </div>
                <motion.div
                    className="relative z-10 px-12 text-center"
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.8 }}
                >
                    <img
                        src={LOGO_SRC}
                        alt="Brasa"
                        className="mx-auto mb-8 size-32 object-contain"
                        width={128}
                        height={128}
                    />
                    <h1 className="mb-3 text-4xl font-bold">
                        <span className="text-gradient-fire">Brasa</span>
                    </h1>
                    <p className="mb-2 text-lg text-muted-foreground">
                        Pantanal — Serra do Amolar
                    </p>
                    <p className="mx-auto max-w-md text-sm text-muted-foreground">
                        Sistema de monitoramento e alerta de incêndios para
                        brigadas de combate. Proteção em tempo real para a
                        biodiversidade do Pantanal.
                    </p>
                </motion.div>
            </div>

            <div className="flex flex-1 flex-col items-center justify-center p-6 lg:p-12">
                <motion.div
                    className="mb-8 flex w-full max-w-md items-center justify-center gap-3 lg:hidden"
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.45 }}
                >
                    <img
                        src={LOGO_SRC}
                        alt="Brasa"
                        className="size-14 object-contain"
                        width={56}
                        height={56}
                    />
                    <div className="text-left">
                        <h1 className="text-xl font-bold">
                            <span className="text-gradient-fire">Brasa</span>
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            Pantanal
                        </p>
                    </div>
                </motion.div>
                <motion.div
                    className="w-full max-w-md"
                    initial={{ opacity: 0, x: 20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ duration: 0.5, delay: 0.2 }}
                >
                    {children}
                </motion.div>
            </div>
        </div>
    );
}
