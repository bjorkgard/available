import { useRef, useState } from 'react';
import { flushSync } from 'react-dom';

import { BookingBlock } from '@/components/booking-block';
import { CalendarContextMenu } from '@/components/calendar-context-menu';
import type { DropTarget } from '@/hooks/use-drag-booking';
import { useNowIndicator } from '@/hooks/use-now-indicator';
import {
    computeOverlapLayout,
    formatDateString,
    formatHour,
    formatTimeFromMinutes,
    getBookingsForDay,
    GRID_HOURS,
} from '@/lib/calendar-utils';
import { getAppLocale } from '@/lib/locale';
import { cn } from '@/lib/utils';
import type { BookingResource, Room } from '@/types';

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

function getBookingsForDayAndRoom(
    bookings: BookingResource[],
    year: number,
    month: number,
    day: number,
    roomId: string,
): BookingResource[] {
    return getBookingsForDay(bookings, year, month, day).filter((b) =>
        b.rooms.some((r) => r.id === roomId),
    );
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

    // Current time indicator
    const { nowPercent, todayDate } = useNowIndicator();

    const dateFormatter = new Intl.DateTimeFormat(getAppLocale(), {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    const formattedDate = dateFormatter.format(
        new Date(date.year, date.month, date.day),
    );

    const dateStr = formatDateString(date.year, date.month, date.day);
    const isTodayLive = dateStr === todayDate;

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
                            isToday && 'font-semibold text-primary',
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
                        Inga rum konfigurerade
                    </div>
                )}
            </div>

            {/* Time grid: hour labels + room columns */}
            <div className="min-h-0 flex-1 overflow-y-auto">
                <div
                    className="grid h-full"
                    style={{
                        gridTemplateColumns: gridColsStyle,
                    }}
                >
                    {/* Hour label column — uses the same grid row structure as room columns */}
                    <div
                        className="row-span-full grid"
                        style={{
                            gridTemplateRows: `repeat(${GRID_HOURS.length}, 1fr)`,
                        }}
                    >
                        {GRID_HOURS.map((hour) => (
                            <div
                                key={hour}
                                className="flex w-14 items-start justify-end border-t border-dashed pt-1 pr-2 text-xs text-muted-foreground"
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
                                        if (
                                            getDropZoneProps &&
                                            draggedBooking
                                        ) {
                                            e.preventDefault();
                                            e.dataTransfer.dropEffect = 'move';

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
                                                const found = roomBookings.find(
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
                                            gridTemplateRows: `repeat(${GRID_HOURS.length}, 1fr)`,
                                        }}
                                    >
                                        {GRID_HOURS.map((hour) => (
                                            <div
                                                key={`${room.id}-${hour}`}
                                                className="border-t border-dashed"
                                            />
                                        ))}
                                    </div>

                                    {/* Booking blocks */}
                                    {roomBookings.map((booking) => {
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
                                        ghostPosition.roomIndex ===
                                            rooms.indexOf(room) && (
                                            <div
                                                className="pointer-events-none absolute right-1 left-1 rounded border-2 border-dashed border-primary/50 bg-primary/10"
                                                style={{
                                                    top: `${ghostPosition.topPercent}%`,
                                                    height: `${ghostPosition.heightPercent}%`,
                                                }}
                                            />
                                        )}

                                    {/* Current time indicator */}
                                    {isTodayLive && (
                                        <div
                                            className="pointer-events-none absolute right-0 left-0 z-10 flex items-center"
                                            style={{ top: `${nowPercent}%` }}
                                        >
                                            <div className="size-2 rounded-full bg-red-500" />
                                            <div className="h-px flex-1 bg-red-500" />
                                        </div>
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
                                <div
                                    className="grid h-full"
                                    style={{
                                        gridTemplateRows: `repeat(${GRID_HOURS.length}, 1fr)`,
                                    }}
                                >
                                    {GRID_HOURS.map((hour) => (
                                        <div
                                            key={hour}
                                            className="border-t border-dashed"
                                        />
                                    ))}
                                </div>
                            </div>
                        </CalendarContextMenu>
                    )}
                </div>
            </div>
        </div>
    );
}
