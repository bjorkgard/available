import { router, usePage } from '@inertiajs/react';
import { Globe } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const LOCALE_LABELS: Record<string, string> = {
    sv: 'Svenska',
    en: 'English',
};

export function LanguageSelector() {
    const { locale, supportedLocales, auth } = usePage<{
        locale: string;
        supportedLocales: string[];
        auth: { user: unknown };
    }>().props;
    const { i18n } = useTranslation();

    function handleSelect(newLocale: string) {
        if (newLocale === locale) {
            return;
        }

        // Eagerly switch the frontend language so translations update immediately
        i18n.changeLanguage(newLocale);

        if (auth.user) {
            router.patch(
                '/settings/locale',
                { locale: newLocale },
                { preserveScroll: true },
            );
        } else {
            router.post(
                '/locale',
                { locale: newLocale },
                { preserveScroll: true },
            );
        }
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="sm"
                    className="w-full justify-start gap-2"
                >
                    <Globe className="size-4" />
                    <span>{LOCALE_LABELS[locale] ?? locale}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {supportedLocales.map((loc) => (
                    <DropdownMenuItem
                        key={loc}
                        onSelect={() => handleSelect(loc)}
                        className={loc === locale ? 'font-medium' : ''}
                    >
                        {LOCALE_LABELS[loc] ?? loc}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
