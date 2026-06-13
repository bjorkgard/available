import {
    Clock,
    DoorOpen,
    PencilIcon,
    Repeat,
    TrashIcon,
    User,
} from 'lucide-react';
import { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';

import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuLabel,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';
import { useLongPress } from '@/hooks/use-long-press';
import { getAppLocale } from '@/lib/locale';
import type { BookingResource } from '@/types';

type BookingContextMenuProps = {
    booking: BookingResource;
    onEdit?: () => void;
    onDelete?: () => void;
    children: React.ReactNode;
};

function formatTimeRange(startsAt: string, endsAt: string): string {
    const start = new Date(startsAt);
    const end = new Date(endsAt);

    const dateFormatter = new Intl.DateTimeFormat(getAppLocale(), {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });

    const timeFormatter = new Intl.DateTimeFormat(getAppLocale(), {
        hour: '2-digit',
        minute: '2-digit',
    });

    const startDate = dateFormatter.format(start);
    const endDate = dateFormatter.format(end);
    const startTime = timeFormatter.format(start);
    const endTime = timeFormatter.format(end);

    if (startDate === endDate) {
        return `${startDate} ${startTime}–${endTime}`;
    }

    return `${startDate} ${startTime} – ${endDate} ${endTime}`;
}

export function BookingContextMenu({
    booking,
    onEdit,
    onDelete,
    children,
}: BookingContextMenuProps) {
    const [open, setOpen] = useState(false);
    const { t } = useTranslation();

    const handleLongPress = useCallback(() => {
        setOpen(true);
    }, []);

    const longPressHandlers = useLongPress({
        onLongPress: handleLongPress,
    });

    const hasActions = booking.can_edit || booking.can_delete;

    return (
        <div onContextMenu={(e) => e.stopPropagation()}>
            <ContextMenu open={open} onOpenChange={setOpen}>
                <ContextMenuTrigger asChild {...longPressHandlers}>
                    {children}
                </ContextMenuTrigger>

                <ContextMenuContent className="w-64">
                    <ContextMenuLabel className="font-semibold">
                        {booking.name}
                    </ContextMenuLabel>

                    <ContextMenuSeparator />

                    <div className="space-y-1 px-2 py-1.5">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Clock className="size-3.5 shrink-0" />
                            <span>
                                {formatTimeRange(
                                    booking.starts_at,
                                    booking.ends_at,
                                )}
                            </span>
                        </div>

                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <DoorOpen className="size-3.5 shrink-0" />
                            <span>
                                {booking.rooms.map((r) => r.name).join(', ')}
                            </span>
                        </div>

                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <User className="size-3.5 shrink-0" />
                            <span>
                                {booking.user_name} ·{' '}
                                {booking.congregation_name}
                            </span>
                        </div>

                        {booking.recurrence_summary && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Repeat className="size-3.5 shrink-0" />
                                <span>{booking.recurrence_summary}</span>
                            </div>
                        )}
                    </div>

                    {hasActions && (
                        <>
                            <ContextMenuSeparator />

                            {booking.can_edit && onEdit && (
                                <ContextMenuItem onSelect={onEdit}>
                                    <PencilIcon />
                                    {t('Redigera')}
                                </ContextMenuItem>
                            )}

                            {booking.can_delete && onDelete && (
                                <ContextMenuItem
                                    variant="destructive"
                                    onSelect={onDelete}
                                >
                                    <TrashIcon />
                                    {t('Ta bort')}
                                </ContextMenuItem>
                            )}
                        </>
                    )}
                </ContextMenuContent>
            </ContextMenu>
        </div>
    );
}
