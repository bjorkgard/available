import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import AppLogoIcon from '@/components/app-logo-icon';
import Aurora from '@/components/aurora';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
    wide,
}: AuthLayoutProps & { wide?: boolean }) {
    const { t } = useTranslation();

    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-background p-6 md:p-10">
            {/* Aurora background */}
            <div className="pointer-events-none absolute inset-0 z-0 opacity-25 dark:opacity-35">
                <Aurora
                    colorStops={['#3b82f6', '#10b981', '#6366f1']}
                    amplitude={1.0}
                    blend={0.6}
                    speed={0.4}
                />
            </div>

            <div
                className={`relative z-10 ${wide ? 'w-full max-w-2xl' : 'w-full max-w-sm'}`}
            >
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                                <AppLogoIcon className="size-9 fill-current text-(--foreground) dark:text-white" />
                            </div>
                            <span className="sr-only">{t(title ?? '')}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">
                                {t(title ?? '')}
                            </h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {t(description ?? '')}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
