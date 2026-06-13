import {
    Clock,
    DoorOpen,
    PencilIcon,
    Plus,
    Repeat,
    TrashIcon,
    User,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuLabel,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/components/ui/context-menu';
import { getAppLocale } from '@/lib/locale';
import type { BookingResource } from '@/types';

type CalendarContextMenuProps = {
    /** The booking that was right-clicked, or null if empty space */
    booking: BookingResource | null;
    onCreateBooking: () => void;
    onEditBooking?: () => void;
    onDeleteBooking?: () => void;
    children: ReactNode;
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

export function CalendarContextMenu({
    booking,
    onCreateBooking,
    onEditBooking,
    onDeleteBooking,
    children,
}: CalendarContextMenuProps) {
    const { t } = useTranslation();

    return (
        <ContextMenu>
            <ContextMenuTrigger asChild>{children}</ContextMenuTrigger>

            <ContextMenuContent className="w-64">
                {/* Booking details section — shown when a booking is targeted */}
                {booking && (
                    <>
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
                                    {booking.rooms
                                        .map((r) => r.name)
                                        .join(', ')}
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

                        <ContextMenuSeparator />
                    </>
                )}

                {/* Create — disabled when a booking is targeted */}
                <ContextMenuItem
                    onSelect={onCreateBooking}
                    disabled={!!booking}
                >
                    <Plus className="size-4" />
                    {t('Ny bokning')}
                </ContextMenuItem>

                {/* Edit — disabled when no booking or user lacks permission */}
                <ContextMenuItem
                    onSelect={onEditBooking}
                    disabled={!booking?.can_edit}
                >
                    <PencilIcon className="size-4" />
                    {t('Redigera')}
                </ContextMenuItem>

                {/* Delete — disabled when no booking or user lacks permission */}
                <ContextMenuItem
                    onSelect={onDeleteBooking}
                    disabled={!booking?.can_delete}
                    className="text-red-600 focus:text-red-600 dark:text-red-400 dark:focus:text-red-400"
                >
                    <TrashIcon className="size-4" />
                    {t('Ta bort')}
                </ContextMenuItem>
            </ContextMenuContent>
        </ContextMenu>
    );
}
