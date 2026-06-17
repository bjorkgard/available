import { useTranslation } from 'react-i18next';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

type ShortcutEntry = {
    keys: string[];
    label: string;
};

type ShortcutGroup = {
    title: string;
    shortcuts: ShortcutEntry[];
};

function Kbd({ children }: { children: string }) {
    return (
        <kbd className="inline-flex h-5 min-w-5 items-center justify-center rounded border border-border bg-muted px-1.5 font-mono text-[11px] font-medium text-muted-foreground">
            {children}
        </kbd>
    );
}

export default function KeyboardShortcutsDialog({ open, onOpenChange }: Props) {
    const { t } = useTranslation();

    const groups: ShortcutGroup[] = [
        {
            title: t('Navigering'),
            shortcuts: [
                { keys: ['←'], label: t('Föregående period') },
                { keys: ['→'], label: t('Nästa period') },
                { keys: ['T'], label: t('Gå till idag') },
            ],
        },
        {
            title: t('Vyer'),
            shortcuts: [
                { keys: ['M'], label: t('Månadsvy') },
                { keys: ['W'], label: t('Veckovy') },
                { keys: ['D'], label: t('Dagsvy') },
            ],
        },
        {
            title: t('Åtgärder'),
            shortcuts: [
                { keys: ['N'], label: t('Ny bokning') },
                { keys: ['L'], label: t('Växla ljust/mörkt läge') },
                { keys: ['?'], label: t('Visa kortkommandon') },
            ],
        },
        {
            title: t('Dra och släpp'),
            shortcuts: [
                {
                    keys: [t('Dra')],
                    label: t('Flytta bokning till annan tid eller dag'),
                },
            ],
        },
    ];

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>{t('Kortkommandon')}</DialogTitle>
                    <DialogDescription>
                        {t(
                            'Använd dessa tangenter för att snabbt navigera och hantera kalendern.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    {groups.map((group) => (
                        <div key={group.title}>
                            <h4 className="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                {group.title}
                            </h4>
                            <ul className="space-y-1.5">
                                {group.shortcuts.map((shortcut) => (
                                    <li
                                        key={shortcut.label}
                                        className="flex items-center justify-between py-0.5"
                                    >
                                        <span className="text-sm">
                                            {shortcut.label}
                                        </span>
                                        <span className="flex items-center gap-1">
                                            {shortcut.keys.map((key) => (
                                                <Kbd key={key}>{key}</Kbd>
                                            ))}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                <p className="mt-2 text-xs text-muted-foreground">
                    {t(
                        'Kortkommandon är inaktiva när du skriver i ett textfält.',
                    )}
                </p>
            </DialogContent>
        </Dialog>
    );
}
