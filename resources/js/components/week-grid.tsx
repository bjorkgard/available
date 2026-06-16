import { useRef, useState } from 'react';
import { flushSync } from 'react-dom';

import { BookingBlock } from '@/components/booking-block';
import { CalendarContextMenu } from '@/components/calendar-context-menu';
import type { DropTarget } from '@/hooks/use-drag-booking';
import { cn } from '@/lib/utils';
import type { BookingResource } from '@/types';

interface WeekDay {
    day: number;
    month: number;
    year: number;
    label: string;
    isToday: boolean;
}

interface WeekGridProps {
    days: WeekDay[];
    bookings: BookingResource[];
    onCreateBooking: (initialDate?: string, initialTime?: string) => void;
    onEditBooking: (booking: BookingResource) => void;
    onDeleteBooking: (booking: BookingResource) => void;
    getDragProps?: (booking: BookingResource) => {
        draggable: boolean;
        onDragStart: (event: React.DragEvent) => void;
        onDragEnd: (event: React.DragEvent) => void;
    };
    getDropZoneProps?: (target: DropTarget) => {
        onDragOver: (event: React.DragEvent) => void;
        onDragLeave: (event: React.DragEvent) => void;
        onDrop: (event: React.DragEvent) => void;
    };
    draggedBookingId?: string | null;
    /** The booking currently being dragged (for ghost preview) */
    draggedBooking?: BookingResource | null;
}

const HOURS = Array.from({ length: 12 }, (_, i) => i * 2); // 0, 2, 4, ..., 22

function formatHour(hour: number): string {
    return `${String(hour).padStart(2, '0')}:00`;
}

function formatDateString(year: number, month: number, day: number): string {
    const m = (month + 1).toString().padStart(2, '0');
    const d = day.toString().padStart(2, '0');

    return `${year}-${m}-${d}`;
}

function formatTimeFromMinutes(minutes: number): string {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;

    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
}

function getBookingsForDay(
    bookings: BookingResource[],
    year: number,
    month: number,
    day: number,
): BookingResource[] {
    const dayStart = new Date(year, month, day);
    const dayEnd = new Date(year, month, day + 1);

    return bookings.filter((b) => {
        const startsAt = new Date(b.starts_at);
        const endsAt = new Date(b.ends_at);

        return startsAt < dayEnd && endsAt > dayStart;
    });
}

/**
 * Computes overlapping groups for side-by-side rendering.
 */
function computeOverlapLayout(
    bookings: BookingResource[],
): Map<string, { column: number; totalColumns: number }> {
    const layout = new Map<string, { column: number; totalColumns: number }>();

    if (bookings.length === 0) {
        return layout;
    }

    const sorted = [...bookings].sort(
        (a, b) =>
            new Date(a.starts_at).getTime() - new Date(b.starts_at).getTime(),
    );

    const groups: BookingResource[][] = [];
    let currentGroup: BookingResource[] = [];
    let groupEnd = -Infinity;

    for (const booking of sorted) {
        const start = new Date(booking.starts_at).getTime();
        const end = new Date(booking.ends_at).getTime();

        if (start >= groupEnd) {
            if (currentGroup.length > 0) {
                groups.push(currentGroup);
            }

            currentGroup = [booking];
            groupEnd = end;
        } else {
            currentGroup.push(booking);
            groupEnd = Math.max(groupEnd, end);
        }
    }

    if (currentGroup.length > 0) {
        groups.push(currentGroup);
    }

    for (const group of groups) {
        const totalColumns = group.length;

        for (let i = 0; i < group.length; i++) {
            layout.set(group[i].id, { column: i, totalColumns });
        }
    }

    return layout;
}

export function WeekGrid({
    days,
    bookings,
    onCreateBooking,
    onEditBooking,
    onDeleteBooking,
    getDragProps,
    getDropZoneProps,
    draggedBookingId,
    draggedBooking,
}: WeekGridProps) {
    const [contextBooking, setContextBooking] =
        useState<BookingResource | null>(null);
    const contextDateRef = useRef<string>('');
    const contextTimeRef = useRef<string | undefined>(undefined);

    // Ghost preview state: tracks which day column + vertical position the user is hovering over
    const [ghostPosition, setGhostPosition] = useState<{
        dayIndex: number;
        topPercent: number;
        heightPercent: number;
    } | null>(null);

    return (
        <div className="grid h-full grid-cols-[auto_repeat(7,1fr)] grid-rows-[auto_1fr]">
            {/* Header row: empty corner + day names */}
            <div className="border-b p-2" />
            {days.map((day) => (
                <div
                    key={`${day.year}-${day.month}-${day.day}`}
                    className={cn(
                        'flex flex-col items-center justify-center border-b p-2 text-sm font-medium',
                        day.isToday && 'font-semibold text-blue-500',
                    )}
                >
                    <span className="text-muted-foreground">{day.label}</span>
                    <span
                        className={cn(
                            'mt-0.5 flex h-7 w-7 items-center justify-center rounded-full text-sm',
                            day.isToday &&
                                'border-2 border-blue-500 font-semibold',
                        )}
                    >
                        {day.day}
                    </span>
                </div>
            ))}

            {/* Time grid: hour labels + day columns with bookings */}
            <div className="col-span-full grid grid-cols-[auto_repeat(7,1fr)] overflow-y-auto">
                <div className="contents">
                    {/* Hour label column — uses the same grid row structure as day columns */}
                    <div
                        className="row-span-full grid"
                        style={{
                            gridTemplateRows: `repeat(${HOURS.length}, 1fr)`,
                        }}
                    >
                        {HOURS.map((hour) => (
                            <div
                                key={hour}
                                className="flex w-14 items-start justify-end border-t border-dashed pt-1 pr-2 text-xs text-muted-foreground"
                            >
                                {formatHour(hour)}
                            </div>
                        ))}
                    </div>

                    {/* Day columns */}
                    {days.map((day) => {
                        const dayBookings = getBookingsForDay(
                            bookings,
                            day.year,
                            day.month,
                            day.day,
                        );
                        const overlapLayout = computeOverlapLayout(dayBookings);
                        const dateStr = formatDateString(
                            day.year,
                            day.month,
                            day.day,
                        );

                        return (
                            <CalendarContextMenu
                                key={`col-${day.year}-${day.month}-${day.day}`}
                                booking={contextBooking}
                                onCreateBooking={() =>
                                    onCreateBooking(
                                        contextDateRef.current,
                                        contextTimeRef.current,
                                    )
                                }
                                onEditBooking={() => {
                                    if (contextBooking) {
                                        onEditBooking(contextBooking);
                                    }
                                }}
                                onDeleteBooking={() => {
                                    if (contextBooking) {
                                        onDeleteBooking(contextBooking);
                                    }
                                }}
                            >
                                <div
                                    className={cn(
                                        'relative border-l',
                                        day.isToday &&
                                            'bg-blue-50/30 dark:bg-blue-950/10',
                                    )}
                                    onDragOver={(e) => {
                                        if (
                                            getDropZoneProps &&
                                            draggedBooking
                                        ) {
                                            e.preventDefault();
                                            e.dataTransfer.dropEffect = 'move';

                                            // Compute ghost position
                                            const rect =
                                                e.currentTarget.getBoundingClientRect();
                                            const relativeY =
                                                e.clientY - rect.top;
                                            const totalHeight = rect.height;
                                            const totalMinutes = Math.round(
                                                (relativeY / totalHeight) *
                                                    24 *
                                                    60,
                                            );
                                            const snappedMinutes =
                                                Math.round(totalMinutes / 15) *
                                                15;
                                            const topPercent =
                                                (snappedMinutes / (24 * 60)) *
                                                100;

                                            // Compute duration of dragged booking
                                            const origStart = new Date(
                                                draggedBooking.starts_at,
                                            );
                                            const origEnd = new Date(
                                                draggedBooking.ends_at,
                                            );
                                            const durationMinutes =
                                                (origEnd.getTime() -
                                                    origStart.getTime()) /
                                                60000;
                                            const heightPercent =
                                                (durationMinutes / (24 * 60)) *
                                                100;

                                            const dayIdx = days.indexOf(day);
                                            setGhostPosition({
                                                dayIndex: dayIdx,
                                                topPercent,
                                                heightPercent,
                                            });
                                        }
                                    }}
                                    onDragLeave={() => {
                                        setGhostPosition(null);
                                    }}
                                    onDrop={(e) => {
                                        setGhostPosition(null);

                                        if (!getDropZoneProps) {
                                            return;
                                        }

                                        e.preventDefault();
                                        const rect =
                                            e.currentTarget.getBoundingClientRect();
                                        const relativeY = e.clientY - rect.top;
                                        const totalHeight = rect.height;
                                        const totalMinutes = Math.round(
                                            (relativeY / totalHeight) * 24 * 60,
                                        );
                                        const snappedMinutes =
                                            Math.round(totalMinutes / 15) * 15;
                                        const hour = Math.floor(
                                            snappedMinutes / 60,
                                        );
                                        const minute = snappedMinutes % 60;

                                        const target: DropTarget = {
                                            date: dateStr,
                                            hour: Math.min(hour, 23),
                                            minute,
                                        };

                                        getDropZoneProps(target).onDrop(e);
                                    }}
                                    onContextMenu={(e) => {
                                        contextDateRef.current = dateStr;
                                        // Compute time from click position
                                        const rect =
                                            e.currentTarget.getBoundingClientRect();
                                        const relativeY = e.clientY - rect.top;
                                        const totalHeight = rect.height;
                                        const totalMinutes = Math.round(
                                            (relativeY / totalHeight) * 24 * 60,
                                        );
                                        const snappedMinutes =
                                            Math.round(totalMinutes / 15) * 15;
                                        contextTimeRef.current =
                                            formatTimeFromMinutes(
                                                Math.min(
                                                    snappedMinutes,
                                                    23 * 60 + 45,
                                                ),
                                            );
                                        // Check if right-click originated from a booking
                                        const target = e.target as HTMLElement;
                                        const bookingEl =
                                            target.closest('[data-booking-id]');

                                        flushSync(() => {
                                            if (bookingEl) {
                                                const id =
                                                    bookingEl.getAttribute(
                                                        'data-booking-id',
                                                    );
                                                const found = dayBookings.find(
                                                    (b) => b.id === id,
                                                );
                                                setContextBooking(
                                                    found ?? null,
                                                );
                                            } else {
                                                setContextBooking(null);
                                            }
                                        });
                                    }}
                                >
                                    {/* Hour grid lines — use grid to match label column heights */}
                                    <div
                                        className="grid h-full"
                                        style={{
                                            gridTemplateRows: `repeat(${HOURS.length}, 1fr)`,
                                        }}
                                    >
                                        {HOURS.map((hour) => (
                                            <div
                                                key={hour}
                                                className="border-t border-dashed"
                                            />
                                        ))}
                                    </div>

                                    {/* Booking blocks */}
                                    {dayBookings.map((booking) => {
                                        const layoutInfo = overlapLayout.get(
                                            booking.id,
                                        );
                                        const column = layoutInfo?.column ?? 0;
                                        const totalColumns =
                                            layoutInfo?.totalColumns ?? 1;
                                        const widthPercent = 100 / totalColumns;
                                        const leftPercent =
                                            column * widthPercent;

                                        return (
                                            <div
                                                key={booking.id}
                                                data-booking-id={booking.id}
                                                {...(getDragProps
                                                    ? getDragProps(booking)
                                                    : {})}
                                                className={
                                                    draggedBookingId ===
                                                    booking.id
                                                        ? 'opacity-40'
                                                        : ''
                                                }
                                            >
                                                <BookingBlock
                                                    booking={booking}
                                                    viewMode="week"
                                                    style={{
                                                        width: `calc(${widthPercent}% - 4px)`,
                                                        left: `calc(${leftPercent}% + 2px)`,
                                                        right: 'auto',
                                                    }}
                                                />
                                            </div>
                                        );
                                    })}

                                    {/* Ghost preview during drag */}
                                    {ghostPosition &&
                                        ghostPosition.dayIndex ===
                                            days.indexOf(day) && (
                                            <div
                                                className="pointer-events-none absolute right-1 left-1 rounded border-2 border-dashed border-primary/50 bg-primary/10"
                                                style={{
                                                    top: `${ghostPosition.topPercent}%`,
                                                    height: `${ghostPosition.heightPercent}%`,
                                                }}
                                            />
                                        )}
                                </div>
                            </CalendarContextMenu>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

export type { WeekDay };
