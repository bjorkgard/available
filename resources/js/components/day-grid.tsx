import { useRef, useState } from 'react';
import { flushSync } from 'react-dom';

import { BookingBlock } from '@/components/booking-block';
import { CalendarContextMenu } from '@/components/calendar-context-menu';
import { APP_LOCALE } from '@/lib/locale';
import { cn } from '@/lib/utils';
import type { BookingResource, Room } from '@/types';

import type { DropTarget } from '@/hooks/use-drag-booking';

interface DayGridProps {
    date: { day: number; month: number; year: number };
    rooms: Room[];
    isToday: boolean;
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

function getBookingsForDayAndRoom(
    bookings: BookingResource[],
    year: number,
    month: number,
    day: number,
    roomId: string,
): BookingResource[] {
    const dayStart = new Date(year, month, day);
    const dayEnd = new Date(year, month, day + 1);

    return bookings.filter((b) => {
        const startsAt = new Date(b.starts_at);
        const endsAt = new Date(b.ends_at);
        const overlapsDay = startsAt < dayEnd && endsAt > dayStart;
        const inRoom = b.rooms.some((r) => r.id === roomId);

        return overlapsDay && inRoom;
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

export function DayGrid({
    date,
    rooms,
    isToday,
    bookings,
    onCreateBooking,
    onEditBooking,
    onDeleteBooking,
    getDragProps,
    getDropZoneProps,
    draggedBookingId,
    draggedBooking,
}: DayGridProps) {
    const roomCount = Math.max(rooms.length, 1);
    const gridColsStyle = `auto repeat(${roomCount}, 1fr)`;

    const [contextBooking, setContextBooking] =
        useState<BookingResource | null>(null);
    const contextTimeRef = useRef<string | undefined>(undefined);

    // Ghost preview state
    const [ghostPosition, setGhostPosition] = useState<{
        roomIndex: number;
        topPercent: number;
        heightPercent: number;
    } | null>(null);

    const dateFormatter = new Intl.DateTimeFormat(APP_LOCALE, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    const formattedDate = dateFormatter.format(
        new Date(date.year, date.month, date.day),
    );

    const dateStr = formatDateString(date.year, date.month, date.day);

    return (
        <div className="flex h-full flex-col">
            {/* Day header with date and room names */}
            <div
                className="grid border-b"
                style={{ gridTemplateColumns: gridColsStyle }}
            >
                <div className="flex w-14 items-center justify-center p-2">
                    <span
                        className={cn(
                            'text-sm font-medium',
                            isToday && 'font-semibold text-blue-500',
                        )}
                    >
                        {formattedDate}
                    </span>
                </div>
                {rooms.map((room) => (
                    <div
                        key={room.id}
                        className="flex items-center justify-center border-l p-2 text-sm font-medium"
                    >
                        {room.name}
                    </div>
                ))}
                {rooms.length === 0 && (
                    <div className="flex items-center justify-center p-2 text-sm text-muted-foreground">
                        No rooms configured
                    </div>
                )}
            </div>

            {/* Time grid: hour labels + room columns */}
            <div className="min-h-0 flex-1 overflow-y-auto">
                <div
                    className="grid"
                    style={{
                        gridTemplateColumns: gridColsStyle,
                    }}
                >
                    {/* Hour label column */}
                    <div className="row-span-full">
                        {HOURS.map((hour) => (
                            <div
                                key={hour}
                                className="flex h-16 w-14 items-start justify-end border-t border-dashed pt-1 pr-2 text-xs text-muted-foreground"
                            >
                                {formatHour(hour)}
                            </div>
                        ))}
                    </div>

                    {/* Room columns with bookings */}
                    {rooms.map((room) => {
                        const roomBookings = getBookingsForDayAndRoom(
                            bookings,
                            date.year,
                            date.month,
                            date.day,
                            room.id,
                        );
                        const overlapLayout =
                            computeOverlapLayout(roomBookings);

                        return (
                            <CalendarContextMenu
                                key={room.id}
                                booking={contextBooking}
                                onCreateBooking={() =>
                                    onCreateBooking(
                                        dateStr,
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
                                    className="relative border-l"
                                    onDragOver={(e) => {
                                        if (getDropZoneProps && draggedBooking) {
                                            e.preventDefault();
                                            e.dataTransfer.dropEffect = 'move';

                                            const rect = e.currentTarget.getBoundingClientRect();
                                            const relativeY = e.clientY - rect.top;
                                            const totalHeight = rect.height;
                                            const totalMinutes = Math.round(
                                                (relativeY / totalHeight) * 24 * 60,
                                            );
                                            const snappedMinutes = Math.round(totalMinutes / 15) * 15;
                                            const topPercent = (snappedMinutes / (24 * 60)) * 100;

                                            const origStart = new Date(draggedBooking.starts_at);
                                            const origEnd = new Date(draggedBooking.ends_at);
                                            const durationMinutes = (origEnd.getTime() - origStart.getTime()) / 60000;
                                            const heightPercent = (durationMinutes / (24 * 60)) * 100;

                                            const roomIdx = rooms.indexOf(room);
                                            setGhostPosition({
                                                roomIndex: roomIdx,
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
                                        const rect = e.currentTarget.getBoundingClientRect();
                                        const relativeY = e.clientY - rect.top;
                                        const totalHeight = rect.height;
                                        const totalMinutes = Math.round(
                                            (relativeY / totalHeight) * 24 * 60,
                                        );
                                        const snappedMinutes = Math.round(totalMinutes / 15) * 15;
                                        const hour = Math.floor(snappedMinutes / 60);
                                        const minute = snappedMinutes % 60;

                                        const target: DropTarget = {
                                            date: dateStr,
                                            hour: Math.min(hour, 23),
                                            minute,
                                        };

                                        getDropZoneProps(target).onDrop(e);
                                    }}
                                    onContextMenu={(e) => {
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
                                        const bookingEl = target.closest(
                                            '[data-booking-id]',
                                        );

                                        flushSync(() => {
                                            if (bookingEl) {
                                                const id =
                                                    bookingEl.getAttribute(
                                                        'data-booking-id',
                                                    );
                                                const found = roomBookings.find(
                                                    (b) => b.id === id,
                                                );
                                                setContextBooking(found ?? null);
                                            } else {
                                                setContextBooking(null);
                                            }
                                        });
                                    }}
                                >
                                    {/* Hour grid lines */}
                                    {HOURS.map((hour) => (
                                        <div
                                            key={`${room.id}-${hour}`}
                                            className="h-16 border-t border-dashed"
                                        />
                                    ))}

                                    {/* Booking blocks */}
                                    {roomBookings.map((booking) => {
                                        const layoutInfo =
                                            overlapLayout.get(booking.id);
                                        const column =
                                            layoutInfo?.column ?? 0;
                                        const totalColumns =
                                            layoutInfo?.totalColumns ?? 1;
                                        const widthPercent =
                                            100 / totalColumns;
                                        const leftPercent =
                                            column * widthPercent;

                                        return (
                                            <div
                                                key={booking.id}
                                                data-booking-id={booking.id}
                                                {...(getDragProps ? getDragProps(booking) : {})}
                                                className={
                                                    draggedBookingId === booking.id
                                                        ? 'opacity-40'
                                                        : ''
                                                }
                                            >
                                                <BookingBlock
                                                    booking={booking}
                                                    viewMode="day"
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
                                        ghostPosition.roomIndex === rooms.indexOf(room) && (
                                            <div
                                                className="pointer-events-none absolute left-1 right-1 rounded border-2 border-dashed border-primary/50 bg-primary/10"
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
                    {rooms.length === 0 && (
                        <CalendarContextMenu
                            booking={null}
                            onCreateBooking={() => onCreateBooking(dateStr)}
                        >
                            <div className="relative border-l">
                                {HOURS.map((hour) => (
                                    <div
                                        key={hour}
                                        className="h-16 border-t border-dashed"
                                    />
                                ))}
                            </div>
                        </CalendarContextMenu>
                    )}
                </div>
            </div>
        </div>
    );
}
