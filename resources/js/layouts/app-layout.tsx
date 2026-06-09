import { useAppearance } from '@/hooks/use-appearance';
import { useKeyboardShortcuts } from '@/hooks/use-keyboard-shortcuts';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    const { appearance, updateAppearance } = useAppearance();

    useKeyboardShortcuts({
        d: () =>
            updateAppearance(appearance === 'light' ? 'dark' : 'light'),
    });

    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs}>
            {children}
        </AppLayoutTemplate>
    );
}
