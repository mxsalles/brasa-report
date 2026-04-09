import { cn } from '@/lib/utils';
import type { AuthLayoutProps } from '@/types';

const LOGO_SRC = '/images/logo-caninde.png';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div
            className={cn(
                'auth-caninde flex min-h-svh flex-col items-center justify-center p-6 md:p-10',
            )}
            style={{
                backgroundColor: 'var(--caninde-auth-canvas)',
            }}
        >
            <div
                className={cn(
                    'w-full max-w-md rounded-xl border border-neutral-200/80 shadow-md shadow-neutral-900/5',
                )}
                style={{ backgroundColor: 'var(--caninde-auth-card)' }}
            >
                <div className="flex flex-col gap-8 px-8 py-10 md:px-10 md:py-10">
                    <div className="flex flex-col items-center gap-5 text-center">
                        <img
                            src={LOGO_SRC}
                            alt="Canindé"
                            className="size-20 object-contain"
                            width={80}
                            height={80}
                        />
                        <div className="space-y-2">
                            <h1
                                className="text-xl font-semibold tracking-tight"
                                style={{ color: 'var(--caninde-auth-heading)' }}
                            >
                                {title}
                            </h1>
                            {description ? (
                                <p className="text-sm leading-relaxed text-neutral-500">
                                    {description}
                                </p>
                            ) : null}
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
