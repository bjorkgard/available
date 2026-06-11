import { useRef, useState } from 'react';
import { flushSync } from 'react-dom';

import { BookingBlock } from '@/components/booking-block';
import { CalendarContextMenu } from '@/components/calendar-context-menu';
import type { DropTarget } from '@/hooks/use-drag-booking';
import type { DateInfo, GridDate } from '@/lib/calendar-utils';
import { getWeekdayNames } from '@/lib/calendar-utils';
import { cn } from '@/lib/utils';
import type { BookingResource } from '@/types';

interface MonthGridProps {
    grid: GridDate[];
    today: DateInfo | null;
    bookings: BookingResource[];
    onFillerDateClick: (year: number, month: number) => void;
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

/** Maximum bookings to display per day cell before showing "+N more" */
const MAX_VISIBLE_BOOKINGS = 4;

function getBookingsForDate(
    bookings: BookingResource[],
    year: number,
    month: number,
    day: number,
): BookingResource[] {
    const dateStart = new Date(year, month, day);
    const dateEnd = new Date(year, month, day + 1);

    return bookings.filter((b) => {
        const startsAt = new Date(b.starts_at);
        const endsAt = new Date(b.ends_at);

        return startsAt < dateEnd && endsAt > dateStart;
    });
}

function formatDateString(year: number, month: number, day: number): string {
    const m = (month + 1).toString().padStart(2, '0');
    const d = day.toString().padStart(2, '0');

    return `${year}-${m}-${d}`;
}

export function MonthGrid({
    grid,
    today,
    bookings,
    onFillerDateClick,
    onCreateBooking,
    onEditBooking,
    onDeleteBooking,
    getDragProps,
    getDropZoneProps,
    draggedBookingId,
    draggedBooking,
}: MonthGridProps) {
    const weekdays = getWeekdayNames();
    const [contextBooking, setContextBooking] =
        useState<BookingResource | null>(null);
    const contextDateRef = useRef<string>('');

    // Track which cell is being hovered during drag
    const [dropHighlightIndex, setDropHighlightIndex] = useState<number | null>(
        null,
    );

    return (
        <div className="grid h-full grid-cols-7 grid-rows-[auto_repeat(6,1fr)]">
            {weekdays.map((name) => (
                <div
                    key={name}
                    className="flex items-center justify-center border-b py-2 text-sm font-medium text-muted-foreground"
                >
                    {name}
                </div>
            ))}

            {grid.map((date, gridIndex) => {
                const isToday =
                    date.isToday &&
                    date.isCurrentMonth &&
                    today !== null &&
                    date.day === today.day &&
                    date.month === today.month &&
                    date.year === today.year;

                if (!date.isCurrentMonth) {
                    const fillerBookings = getBookingsForDate(
                        bookings,
                        date.year,
                        date.month,
                        date.day,
                    );
                    const fillerVisible =
                        fillerBookings.length > MAX_VISIBLE_BOOKINGS
                            ? fillerBookings.slice(0, 3)
                            : fillerBookings;
                    const fillerMore =
                        fillerBookings.length - fillerVisible.length;

                    return (
                        <div
                            key={gridIndex}
                            role="button"
                            tabIndex={0}
                            onClick={() =>
                                onFillerDateClick(date.year, date.month)
                            }
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    onFillerDateClick(date.year, date.month);
                                }
                            }}
                            onDragOver={(e) => {
                                if (getDropZoneProps) {
                                    e.preventDefault();
                                    e.dataTransfer.dropEffect = 'move';
                                    setDropHighlightIndex(gridIndex);
                                }
                            }}
                            onDragLeave={() => {
                                setDropHighlightIndex(null);
                            }}
                            onDrop={(e) => {
                                setDropHighlightIndex(null);

                                if (!getDropZoneProps || !draggedBooking) {
                                    return;
                                }

                                e.preventDefault();
                                const fillerDateStr = formatDateString(
                                    date.year,
                                    date.month,
                                    date.day,
                                );
                                const origStart = new Date(
                                    draggedBooking.starts_at,
                                );
                                const target: DropTarget = {
                                    date: fillerDateStr,
                                    hour: origStart.getHours(),
                                    minute: origStart.getMinutes(),
                                };

                                getDropZoneProps(target).onDrop(e);
                            }}
                            className={cn(
                                'flex flex-col items-start border p-2 text-muted-foreground hover:bg-accent',
                                dropHighlightIndex === gridIndex &&
                                    'bg-primary/5 ring-2 ring-primary/30 ring-inset',
                            )}
                        >
                            <span className="text-sm leading-none">
                                {date.day}
                            </span>
                            <div className="mt-1 flex w-full flex-1 flex-col gap-0.5 overflow-y-auto opacity-60">
                                {fillerVisible.map((booking) => (
                                    <div
                                        key={booking.id}
                                        data-booking-id={booking.id}
                                        {...(getDragProps
                                            ? getDragProps(booking)
                                            : {})}
                                        className={
                                            draggedBookingId === booking.id
                                                ? 'opacity-40'
                                                : ''
                                        }
                                    >
                                        <BookingBlock
                                            booking={booking}
                                            viewMode="month"
                                        />
                                    </div>
                                ))}
                                {fillerMore > 0 && (
                                    <span className="px-1 text-xs font-medium">
                                        +{fillerMore} more
                                    </span>
                                )}
                            </div>
                        </div>
                    );
                }

                const dayBookings = getBookingsForDate(
                    bookings,
                    date.year,
                    date.month,
                    date.day,
                );
                const visibleBookings =
                    dayBookings.length > MAX_VISIBLE_BOOKINGS
                        ? dayBookings.slice(0, 3)
                        : dayBookings;
                const moreCount = dayBookings.length - visibleBookings.length;

                const dateStr = formatDateString(
                    date.year,
                    date.month,
                    date.day,
                );

                return (
                    <CalendarContextMenu
                        key={gridIndex}
                        booking={contextBooking}
                        onCreateBooking={() =>
                            onCreateBooking(contextDateRef.current)
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
                                'flex flex-col items-start overflow-hidden border p-2',
                                isToday &&
                                    'rounded-md border-2 border-blue-500 font-semibold',
                                dropHighlightIndex === gridIndex &&
                                    'bg-primary/5 ring-2 ring-primary/30 ring-inset',
                            )}
                            onDragOver={(e) => {
                                if (getDropZoneProps) {
                                    e.preventDefault();
                                    e.dataTransfer.dropEffect = 'move';
                                    setDropHighlightIndex(gridIndex);
                                }
                            }}
                            onDragLeave={() => {
                                setDropHighlightIndex(null);
                            }}
                            onDrop={(e) => {
                                setDropHighlightIndex(null);

                                if (!getDropZoneProps || !draggedBooking) {
                                    return;
                                }

                                e.preventDefault();
                                // Keep the original time, just change the date
                                const origStart = new Date(
                                    draggedBooking.starts_at,
                                );
                                const target: DropTarget = {
                                    date: dateStr,
                                    hour: origStart.getHours(),
                                    minute: origStart.getMinutes(),
                                };

                                getDropZoneProps(target).onDrop(e);
                            }}
                            onContextMenu={(e) => {
                                contextDateRef.current = dateStr;
                                // Check if the right-click originated from a booking
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
                                        setContextBooking(found ?? null);
                                    } else {
                                        setContextBooking(null);
                                    }
                                });
                            }}
                        >
                            <span className="text-sm leading-none">
                                {date.day}
                            </span>
                            <div className="mt-1 flex w-full flex-1 flex-col gap-0.5 overflow-y-auto">
                                {visibleBookings.map((booking) => (
                                    <div
                                        key={booking.id}
                                        data-booking-id={booking.id}
                                        {...(getDragProps
                                            ? getDragProps(booking)
                                            : {})}
                                        className={
                                            draggedBookingId === booking.id
                                                ? 'opacity-40'
                                                : ''
                                        }
                                    >
                                        <BookingBlock
                                            booking={booking}
                                            viewMode="month"
                                        />
                                    </div>
                                ))}
                                {moreCount > 0 && (
                                    <span className="px-1 text-xs font-medium text-muted-foreground">
                                        +{moreCount} more
                                    </span>
                                )}
                            </div>
                        </div>
                    </CalendarContextMenu>
                );
            })}
        </div>
    );
}
