import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { useTranslation } from 'react-i18next';
import Heading from '@/components/heading';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { edit as editSessions } from '@/routes/sessions';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profil',
        href: edit(),
        icon: null,
    },
    {
        title: 'Säkerhet',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'Sessioner',
        href: editSessions(),
        icon: null,
    },
    {
        title: 'Utseende',
        href: editAppearance(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { t } = useTranslation();
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="px-4 py-6">
            <Heading
                title={t('Inställningar')}
                description={t('Hantera din profil och kontoinställningar')}
            />

            <div className="flex flex-col lg:flex-row lg:gap-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav
                        className="flex flex-row gap-1 overflow-x-auto lg:flex-col"
                        aria-label={t('Inställningar')}
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Link
                                key={`${toUrl(item.href)}-${index}`}
                                href={item.href}
                                className={cn(
                                    'inline-flex items-center rounded-md px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors',
                                    isCurrentOrParentUrl(item.href)
                                        ? 'bg-muted text-foreground'
                                        : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                                )}
                            >
                                {t(item.title)}
                            </Link>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
