import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import type { AuthHeaderIcon } from '@/types';

export default function AuthLayout({
    children,
    title,
    description,
    headerIcon,
    ...props
}: {
    children: React.ReactNode;
    title: string;
    description?: string;
    headerIcon?: AuthHeaderIcon;
}) {
    return (
        <AuthLayoutTemplate
            title={title}
            description={description}
            headerIcon={headerIcon}
            {...props}
        >
            {children}
        </AuthLayoutTemplate>
    );
}
