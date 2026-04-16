import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    LayoutGrid,
    Map,
    PlusCircle,
    Shield,
    Users,
} from 'lucide-react';

import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import {
    administracao,
    alertas,
    brigadas,
    dashboard,
    mapa,
    registrarIncendio,
} from '@/routes';
import type { NavItem } from '@/types';
import type { FuncaoUsuario } from '@/types/auth';
import AppLogo from './app-logo';

const mainNavItemsBase: NavItem[] = [
    {
        title: 'Painel',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Mapa',
        href: mapa(),
        icon: Map,
    },
    {
        title: 'Registrar',
        href: registrarIncendio(),
        icon: PlusCircle,
    },
    {
        title: 'Alertas',
        href: alertas(),
        icon: Bell,
    },
    {
        title: 'Brigadas',
        href: brigadas(),
        icon: Users,
    },
];

const itemAdministracao: NavItem = {
    title: 'Administração',
    href: administracao(),
    icon: Shield,
};

export function AppSidebar() {
    const {
        auth: { user },
    } = usePage<{ auth: { user: { funcao?: FuncaoUsuario } } }>().props;

    const funcao = user?.funcao;
    const podeVerAdministracao =
        funcao === 'gestor' || funcao === 'administrador';

    const mainNavItems: NavItem[] = podeVerAdministracao
        ? [...mainNavItemsBase, itemAdministracao]
        : mainNavItemsBase;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
