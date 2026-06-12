import type { CSSProperties } from 'react';

import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { APP_LOCALE } from '@/lib/locale';
import { cn } from '@/lib/utils';

export type ViewMode = 'month' | 'week' | 'day';

export interface BookingBlockData {
    id: string;
    name: string;
    starts_at: string;
    ends_at: string;
    congregation_color: string | null;
    can_edit: boolean;
}

interface BookingBlockProps {
    booking: BookingBlockData;
    viewMode: ViewMode;
    style?: CSSProperties;
}

const timeFormatter = new Intl.DateTimeFormat(APP_LOCALE, {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
});

/**
 * Computes the top offset and height (as percentages of the day container)
 * for a booking in week/day views based on a 15-minute grid.
 *
 * A full day has 24 hours × 4 slots = 96 slots.
 */
function computeGridPosition(startsAt: string, endsAt: string) {
    const start = new Date(startsAt);
    const end = new Date(endsAt);

    const startMinutes = start.getHours() * 60 + start.getMinutes();
    const endMinutes = end.getHours() * 60 + end.getMinutes();

    const totalMinutesInDay = 24 * 60;
    const topPercent = (startMinutes / totalMinutesInDay) * 100;
    const heightPercent =
        ((endMinutes - startMinutes) / totalMinutesInDay) * 100;

    return { topPercent, heightPercent };
}

export function BookingBlock({ booking, viewMode, style }: BookingBlockProps) {
    const { name, starts_at, ends_at, congregation_color, can_edit } = booking;

    const startTime = timeFormatter.format(new Date(starts_at));
    const endTime = timeFormatter.format(new Date(ends_at));
    const tooltipText = `${name} ${startTime}–${endTime}`;

    const colorStyles: CSSProperties = congregation_color
        ? {
              backgroundColor: `${congregation_color}20`,
              borderLeftColor: congregation_color,
          }
        : {};

    if (viewMode === 'month') {
        return (
            <Tooltip>
                <TooltipTrigger asChild>
                    <div
                        draggable={can_edit}
                        className={cn(
                            'truncate rounded border-l-2 px-1.5 py-0.5 text-xs leading-tight',
                            !congregation_color &&
                                'border-l-primary/60 bg-primary/10',
                            can_edit && 'cursor-grab',
                        )}
                        style={{ ...colorStyles, ...style }}
                    >
                        <span className="font-medium">{startTime}</span>{' '}
                        <span className="text-muted-foreground">{name}</span>
                    </div>
                </TooltipTrigger>
                <TooltipContent>{tooltipText}</TooltipContent>
            </Tooltip>
        );
    }

    // Week and day views: pixel-accurate positioning
    const { topPercent, heightPercent } = computeGridPosition(
        starts_at,
        ends_at,
    );

    const positionStyles: CSSProperties = {
        position: 'absolute',
        top: `${topPercent}%`,
        height: `${heightPercent}%`,
        left: '2px',
        right: '2px',
        ...colorStyles,
        ...style,
    };

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <div
                    draggable={can_edit}
                    className={cn(
                        'overflow-hidden rounded border-l-2 px-1.5 py-0.5 text-xs leading-tight',
                        !congregation_color &&
                            'border-l-primary/60 bg-primary/10',
                        can_edit && 'cursor-grab',
                    )}
                    style={positionStyles}
                >
                    <span className="font-medium">
                        {startTime}–{endTime}
                    </span>
                    <div className="truncate">{name}</div>
                </div>
            </TooltipTrigger>
            <TooltipContent>{tooltipText}</TooltipContent>
        </Tooltip>
    );
}
