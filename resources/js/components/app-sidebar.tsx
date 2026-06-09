import { Link, usePage } from '@inertiajs/react';
import { Building2, CalendarDays, Church, Users } from 'lucide-react';

import AppLogo from '@/components/app-logo';
import { CongregationSwitcher } from '@/components/congregation-switcher';
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
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const currentCongregation = page.props.currentCongregation;
    const congregations = (page.props.congregations ?? []) as Array<{
        id: string;
    }>;
    const role = page.props.currentCongregationRole;
    const slug = currentCongregation?.slug;

    const isAdmin = role === 'admin' || role === 'superadmin';
    const isSuperadmin = role === 'superadmin';

    const mainNavItems: NavItem[] = [
        {
            title: 'Calendar',
            href: slug ? `/${slug}/dashboard` : '/',
            icon: CalendarDays,
        },
        ...(isAdmin
            ? [
                  {
                      title: 'Members',
                      href: slug ? `/${slug}/members` : '#',
                      icon: Users,
                  },
                  {
                      title: 'Congregation',
                      href: slug ? `/${slug}/congregation` : '#',
                      icon: Church,
                  },
              ]
            : []),
        ...(isSuperadmin
            ? [
                  {
                      title: 'Kingdom Hall',
                      href: slug ? `/${slug}/kingdom-hall` : '#',
                      icon: Building2,
                  },
              ]
            : []),
    ];

    const dashboardUrl = slug ? `/${slug}/dashboard` : '/';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                {congregations.length > 1 && (
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <CongregationSwitcher />
                        </SidebarMenuItem>
                    </SidebarMenu>
                )}
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
