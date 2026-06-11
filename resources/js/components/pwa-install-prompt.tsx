import { EllipsisVerticalIcon, PlusSquareIcon, ShareIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Platform = 'ios' | 'android' | null;

function detectPlatform(): Platform {
    if (typeof navigator === 'undefined') {
        return null;
    }

    const ua = navigator.userAgent || '';

    if (/iPad|iPhone|iPod/.test(ua)) {
        return 'ios';
    }

    if (/Android/.test(ua)) {
        return 'android';
    }

    return null;
}

function isStandalone(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        ('standalone' in navigator &&
            (navigator as { standalone: boolean }).standalone === true)
    );
}

const DISMISSED_KEY = 'pwa-install-dismissed';
const REMIND_LATER_KEY = 'pwa-install-remind-later';
const REMIND_DELAY_MS = 7 * 24 * 60 * 60 * 1000; // 7 days

export function PwaInstallPrompt() {
    const [open, setOpen] = useState(false);

    const platform = detectPlatform();

    useEffect(() => {
        if (!platform) {
            return;
        }

        if (isStandalone()) {
            return;
        }

        const dismissed = localStorage.getItem(DISMISSED_KEY);

        if (dismissed) {
            return;
        }

        const remindLater = localStorage.getItem(REMIND_LATER_KEY);

        if (remindLater) {
            const remindAt = Number(remindLater);

            if (Date.now() < remindAt) {
                return;
            }

            // Reminder period has passed, clear it
            localStorage.removeItem(REMIND_LATER_KEY);
        }

        const timer = setTimeout(() => setOpen(true), 1500);

        return () => clearTimeout(timer);
    }, [platform]);

    function handleRemindLater() {
        setOpen(false);
        localStorage.setItem(
            REMIND_LATER_KEY,
            String(Date.now() + REMIND_DELAY_MS),
        );
    }

    function handleDismiss() {
        setOpen(false);
        localStorage.setItem(DISMISSED_KEY, Date.now().toString());
    }

    if (!platform) {
        return null;
    }

    return (
        <Dialog
            open={open}
            onOpenChange={(isOpen) => {
                if (!isOpen) {
                    handleRemindLater();
                }
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Installera som app</DialogTitle>
                    <DialogDescription>
                        Få en bättre upplevelse genom att lägga till JWAPP på
                        din hemskärm.
                    </DialogDescription>
                </DialogHeader>

                {platform === 'ios' ? (
                    <div className="space-y-4 py-2">
                        <Step number={1}>
                            Tryck på{' '}
                            <ShareIcon className="inline size-4 align-text-bottom" />{' '}
                            <strong>Dela</strong>-knappen i Safari (längst ner
                            på skärmen).
                        </Step>
                        <Step number={2}>
                            Scrolla ner och tryck på{' '}
                            <PlusSquareIcon className="inline size-4 align-text-bottom" />{' '}
                            <strong>Lägg till på hemskärmen</strong>.
                        </Step>
                        <Step number={3}>
                            Tryck <strong>Lägg till</strong> uppe till höger.
                        </Step>
                    </div>
                ) : (
                    <div className="space-y-4 py-2">
                        <Step number={1}>
                            Tryck på{' '}
                            <EllipsisVerticalIcon className="inline size-4 align-text-bottom" />{' '}
                            <strong>Meny</strong>-knappen i Chrome (uppe till
                            höger).
                        </Step>
                        <Step number={2}>
                            Tryck på <strong>Lägg till på startskärmen</strong>{' '}
                            eller <strong>Installera app</strong>.
                        </Step>
                        <Step number={3}>
                            Bekräfta genom att trycka{' '}
                            <strong>Installera</strong>.
                        </Step>
                    </div>
                )}

                <DialogFooter className="gap-2 sm:flex-row">
                    <Button variant="ghost" onClick={handleDismiss}>
                        Visa inte igen
                    </Button>
                    <Button variant="outline" onClick={handleRemindLater}>
                        Jag förstår
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function Step({
    number,
    children,
}: {
    number: number;
    children: React.ReactNode;
}) {
    return (
        <div className="flex gap-3">
            <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground">
                {number}
            </span>
            <p className="text-sm leading-relaxed">{children}</p>
        </div>
    );
}
