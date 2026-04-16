import type { ReactNode } from 'react';
import type { BreadcrumbItem } from './navigation';

export type AppLayoutProps = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

export type AuthHeaderIcon = 'user' | 'key';

export type AuthLayoutProps = {
    children?: ReactNode;
    name?: string;
    title?: string;
    description?: string;
    headerIcon?: AuthHeaderIcon;
};
