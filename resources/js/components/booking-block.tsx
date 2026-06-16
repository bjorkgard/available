import { Repeat, User, DoorOpen } from 'lucide-react';
import type { CSSProperties } from 'react';

import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { getAppLocale } from '@/lib/locale';
import { cn } from '@/lib/utils';

export type ViewMode = 'month' | 'week' | 'day';

export interface BookingBlockData {
    id: string;
    name: string;
    starts_at: string;
    ends_at: string;
    congregation_color: string | null;
    congregation_name: string;
    user_name: string;
    rooms: { id: string; name: string }[];
    recurrence_pattern_id: string | null;
    can_edit: boolean;
}

interface BookingBlockProps {
    booking: BookingBlockData;
    viewMode: ViewMode;
    style?: CSSProperties;
}

function getTimeFormatter() {
    return new Intl.DateTimeFormat(getAppLocale(), {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
}

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

/**
 * Returns the booking duration in minutes.
 */
function getDurationMinutes(startsAt: string, endsAt: string): number {
    const start = new Date(startsAt);
    const end = new Date(endsAt);

    return (end.getTime() - start.getTime()) / 60000;
}

export function BookingBlock({ booking, viewMode, style }: BookingBlockProps) {
    const {
        name,
        starts_at,
        ends_at,
        congregation_color,
        congregation_name,
        user_name,
        rooms,
        recurrence_pattern_id,
        can_edit,
    } = booking;

    const startTime = getTimeFormatter().format(new Date(starts_at));
    const endTime = getTimeFormatter().format(new Date(ends_at));

    const tooltipLines = [
        name,
        `${startTime}–${endTime}`,
        congregation_name,
        rooms.length > 0 ? rooms.map((r) => r.name).join(', ') : null,
        user_name,
    ].filter(Boolean);

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
                <TooltipContent>
                    <div className="space-y-0.5">
                        {tooltipLines.map((line, i) => (
                            <div key={i}>{line}</div>
                        ))}
                    </div>
                </TooltipContent>
            </Tooltip>
        );
    }

    // Week and day views: pixel-accurate positioning
    const { topPercent, heightPercent } = computeGridPosition(
        starts_at,
        ends_at,
    );
    const duration = getDurationMinutes(starts_at, ends_at);

    // Adaptive content tiers based on block height (booking duration)
    const isCompact = duration < 30;
    const isShort = duration >= 30 && duration < 60;
    const isMedium = duration >= 60 && duration < 120;
    // Anything >= 120 is "tall" and shows all details

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
                        'flex flex-col overflow-hidden rounded border-l-[3px] px-2 py-1 text-xs leading-tight',
                        !congregation_color &&
                            'border-l-primary/60 bg-primary/10',
                        can_edit && 'cursor-grab',
                    )}
                    style={positionStyles}
                >
                    {/* Compact: single line with time + name */}
                    {isCompact && (
                        <div className="flex items-center gap-1 truncate">
                            <span className="font-semibold">{startTime}</span>
                            <span className="truncate text-foreground/80">
                                {name}
                            </span>
                            {recurrence_pattern_id && (
                                <Repeat className="size-2.5 shrink-0 text-muted-foreground" />
                            )}
                        </div>
                    )}

                    {/* Short: time header + name */}
                    {isShort && (
                        <>
                            <div className="flex items-center gap-1">
                                <span className="font-semibold text-foreground/70">
                                    {startTime}–{endTime}
                                </span>
                                {recurrence_pattern_id && (
                                    <Repeat className="size-2.5 shrink-0 text-muted-foreground" />
                                )}
                            </div>
                            <div className="mt-0.5 truncate font-medium">
                                {name}
                            </div>
                        </>
                    )}

                    {/* Medium: time, name, congregation */}
                    {isMedium && (
                        <>
                            <div className="flex items-center gap-1">
                                <span className="font-semibold text-foreground/70">
                                    {startTime}–{endTime}
                                </span>
                                {recurrence_pattern_id && (
                                    <Repeat className="size-2.5 shrink-0 text-muted-foreground" />
                                )}
                            </div>
                            <div className="mt-0.5 truncate font-medium">
                                {name}
                            </div>
                            <div className="mt-auto flex items-center gap-1 truncate text-foreground/60">
                                <span className="truncate">
                                    {congregation_name}
                                </span>
                            </div>
                        </>
                    )}

                    {/* Tall: full details */}
                    {!isCompact && !isShort && !isMedium && (
                        <>
                            <div className="flex items-center gap-1">
                                <span className="font-semibold text-foreground/70">
                                    {startTime}–{endTime}
                                </span>
                                {recurrence_pattern_id && (
                                    <Repeat className="size-3 shrink-0 text-muted-foreground" />
                                )}
                            </div>
                            <div className="mt-1 truncate text-[13px] leading-snug font-medium">
                                {name}
                            </div>
                            <div className="mt-auto space-y-0.5 text-foreground/60">
                                {/* Show rooms in week view (day view already uses room columns) */}
                                {viewMode === 'week' && rooms.length > 0 && (
                                    <div className="flex items-center gap-1 truncate">
                                        <DoorOpen className="size-3 shrink-0" />
                                        <span className="truncate">
                                            {rooms
                                                .map((r) => r.name)
                                                .join(', ')}
                                        </span>
                                    </div>
                                )}
                                <div className="flex items-center gap-1 truncate">
                                    <User className="size-3 shrink-0" />
                                    <span className="truncate">
                                        {user_name}
                                    </span>
                                </div>
                                <div className="truncate">
                                    {congregation_name}
                                </div>
                            </div>
                        </>
                    )}
                </div>
            </TooltipTrigger>
            <TooltipContent>
                <div className="space-y-0.5">
                    {tooltipLines.map((line, i) => (
                        <div key={i}>{line}</div>
                    ))}
                </div>
            </TooltipContent>
        </Tooltip>
    );
}
