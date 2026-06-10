import { Head, usePage } from '@inertiajs/react';
import { WifiOff } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';

import {
    destroy,
    index,
} from '@/actions/App/Http/Controllers/Congregations/BookingController';
import BookingDialog from '@/components/booking-dialog';
import { CalendarHeader } from '@/components/calendar-header';
import { DayGrid } from '@/components/day-grid';
import DeleteConfirmDialog from '@/components/delete-confirm-dialog';
import type { DeleteScope } from '@/components/delete-confirm-dialog';
import { ErrorBoundary } from '@/components/error-boundary';
import { MonthGrid } from '@/components/month-grid';
import RecurrenceEditPrompt from '@/components/recurrence-edit-prompt';
import type { RecurrenceEditScope } from '@/components/recurrence-edit-prompt';
import { WeekGrid } from '@/components/week-grid';
import { useBookingChannel } from '@/hooks/use-booking-channel';
import type {
    BookingCreatedEvent,
    BookingDeletedEvent,
    BookingUpdatedEvent,
} from '@/hooks/use-booking-channel';
import { useDragBooking } from '@/hooks/use-drag-booking';
import { useKeyboardShortcuts } from '@/hooks/use-keyboard-shortcuts';
import { useResponsiveViewMode } from '@/hooks/use-responsive-view-mode';
import {
    generateMonthGrid,
    getNextDay,
    getNextMonth,
    getNextWeek,
    getPreviousDay,
    getPreviousMonth,
    getPreviousWeek,
    getWeekDays,
} from '@/lib/calendar-utils';
import type { DateInfo } from '@/lib/calendar-utils';
import { calendar } from '@/routes';
import type { BookingResource, Congregation, KingdomHall, Room } from '@/types';

export default function Calendar() {
    const { currentCongregation } = usePage<{
        currentCongregation?: {
            slug: string;
            id: string;
            kingdom_hall_id: string | null;
            kingdom_hall?: KingdomHall;
        };
    }>().props;

    const rooms: Room[] = currentCongregation?.kingdom_hall?.rooms ?? [];
    const congregations: Congregation[] =
        currentCongregation?.kingdom_hall?.congregations ?? [];
    const kingdomHallId: string | undefined =
        currentCongregation?.kingdom_hall?.id ??
        currentCongregation?.kingdom_hall_id ??
        undefined;

    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();
    const currentDay = now.getDate();

    const { viewMode, setViewMode } = useResponsiveViewMode();
    const [displayedYear, setDisplayedYear] = useState(currentYear);
    const [displayedMonth, setDisplayedMonth] = useState(currentMonth);
    const [displayedDay, setDisplayedDay] = useState(currentDay);

    // Booking state
    const [bookings, setBookings] = useState<BookingResource[]>([]);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [dialogInitialDate, setDialogInitialDate] = useState<
        string | undefined
    >();
    const [dialogInitialTime, setDialogInitialTime] = useState<
        string | undefined
    >();
    const [editingBooking, setEditingBooking] = useState<
        BookingResource | undefined
    >();
    const [deletingBooking, setDeletingBooking] = useState<
        BookingResource | null
    >(null);

    // Drag-and-drop recurrence scope prompt state
    const [dragScopePromptOpen, setDragScopePromptOpen] = useState(false);
    const dragScopeResolverRef = useRef<
        ((scope: RecurrenceEditScope | null) => void) | null
    >(null);

    const { getDragProps, getDropZoneProps, state: dragState } = useDragBooking({
        congregationSlug: currentCongregation?.slug ?? '',
        onRecurrencePrompt: () => {
            return new Promise<RecurrenceEditScope | null>((resolve) => {
                dragScopeResolverRef.current = resolve;
                setDragScopePromptOpen(true);
            });
        },
        onRescheduleSuccess: (bookingId, newStartsAt, newEndsAt) => {
            setBookings((prev) =>
                prev.map((b) =>
                    b.id === bookingId
                        ? { ...b, starts_at: newStartsAt, ends_at: newEndsAt }
                        : b,
                ),
            );
        },
        onRevert: () => {
            refetchBookings();
        },
    });

    function handleDragScopeSelect(scope: RecurrenceEditScope) {
        setDragScopePromptOpen(false);
        dragScopeResolverRef.current?.(scope);
        dragScopeResolverRef.current = null;
    }

    function handleDragScopeCancel() {
        setDragScopePromptOpen(false);
        dragScopeResolverRef.current?.(null);
        dragScopeResolverRef.current = null;
    }

    const minYear = currentYear - 10;
    const maxYear = currentYear + 10;

    // Month grid data
    const grid = generateMonthGrid(displayedYear, displayedMonth);
    const isCurrentMonth =
        displayedYear === currentYear && displayedMonth === currentMonth;
    const today: DateInfo | null = isCurrentMonth
        ? { day: currentDay, month: currentMonth, year: currentYear }
        : null;

    // Week grid data
    const weekDays = getWeekDays(displayedYear, displayedMonth, displayedDay);

    // Day grid data
    const isDayToday =
        displayedYear === currentYear &&
        displayedMonth === currentMonth &&
        displayedDay === currentDay;

    // Navigation for "isToday" button state
    const isTodayVisible =
        viewMode === 'month'
            ? isCurrentMonth
            : viewMode === 'week'
              ? weekDays.some((d) => d.isToday)
              : isDayToday;

    // Calculate the visible date range as stable strings for fetching bookings.
    // Using strings avoids reference instability (new Date objects on every render).
    const dateRangeKey = useMemo(() => {
        if (viewMode === 'month') {
            const firstDate = grid[0];
            const lastDate = grid[grid.length - 1];

            if (!firstDate || !lastDate) {
                return { from: '', to: '' };
            }

            const from = `${firstDate.year}-${String(firstDate.month + 1).padStart(2, '0')}-${String(firstDate.day).padStart(2, '0')}`;
            const to = `${lastDate.year}-${String(lastDate.month + 1).padStart(2, '0')}-${String(lastDate.day).padStart(2, '0')}`;

            return { from, to };
        }

        if (viewMode === 'week') {
            const firstDay = weekDays[0];
            const lastDay = weekDays[weekDays.length - 1];

            if (!firstDay || !lastDay) {
                return { from: '', to: '' };
            }

            const from = `${firstDay.year}-${String(firstDay.month + 1).padStart(2, '0')}-${String(firstDay.day).padStart(2, '0')}`;
            const to = `${lastDay.year}-${String(lastDay.month + 1).padStart(2, '0')}-${String(lastDay.day).padStart(2, '0')}`;

            return { from, to };
        }

        // Day view
        const from = `${displayedYear}-${String(displayedMonth + 1).padStart(2, '0')}-${String(displayedDay).padStart(2, '0')}`;

        return { from, to: from };
    }, [viewMode, displayedYear, displayedMonth, displayedDay, grid, weekDays]);

    // Real-time channel handlers — the useBookingChannel hook stores these
    // in a ref internally so they always see the latest dateRangeKey without
    // needing stable references.
    function handleBookingCreated(event: BookingCreatedEvent) {
        const newBookings = event.bookings ?? [];

        if (newBookings.length === 0) {
            return;
        }

        const userName = newBookings[0]?.user_name ?? 'Someone';

        toast.info(`${userName} created a booking`);

        // Filter to only include bookings within the current visible range
        const fromDate = new Date(dateRangeKey.from);
        const toDate = new Date(dateRangeKey.to + 'T23:59:59.999');

        const filtered = newBookings.filter((b) => {
            const start = new Date(b.starts_at);
            const end = new Date(b.ends_at);

            return start < toDate && end > fromDate;
        });

        if (filtered.length === 0) {
            return;
        }

        setBookings((prev) => {
            const existingIds = new Set(prev.map((b) => b.id));
            const toAdd = filtered.filter((b) => !existingIds.has(b.id));

            if (toAdd.length === 0) {
                return prev;
            }

            return [...prev, ...toAdd];
        });
    }

    function handleBookingUpdated(event: BookingUpdatedEvent) {
        const updatedBookings = event.bookings ?? [];
        const userName = updatedBookings[0]?.user_name ?? 'Someone';

        toast.info(`${userName} updated a booking`);
        refetchBookings();
    }

    function handleBookingDeleted(event: BookingDeletedEvent) {
        const idsToRemove = new Set<string>(event.booking_ids ?? []);
        const userName = event.user_name || 'Someone';

        toast.info(`${userName} deleted a booking`);
        setBookings((prev) => prev.filter((b) => !idsToRemove.has(b.id)));
    }

    // Subscribe to real-time booking updates for the Kingdom Hall
    const connectionStatus = useBookingChannel(kingdomHallId, {
        onCreated: handleBookingCreated,
        onUpdated: handleBookingUpdated,
        onDeleted: handleBookingDeleted,
    });

    // Fetch bookings whenever the visible date range changes
    useEffect(() => {
        if (!currentCongregation?.slug) {
            return;
        }

        const url = index.url(currentCongregation.slug, {
            query: { from: dateRangeKey.from, to: dateRangeKey.to },
        });

        let cancelled = false;

        async function load() {
            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (response.ok && !cancelled) {
                    const json = (await response.json()) as {
                        data: BookingResource[];
                    };
                    setBookings(json.data);
                }
            } catch {
                // Silently fail — bookings will appear empty
            }
        }

        load();

        return () => {
            cancelled = true;
        };
    }, [currentCongregation?.slug, dateRangeKey.from, dateRangeKey.to]);

    // Refetch helper for after dialog closes
    function refetchBookings() {
        if (!currentCongregation?.slug) {
            return;
        }

        const url = index.url(currentCongregation.slug, {
            query: { from: dateRangeKey.from, to: dateRangeKey.to },
        });

        fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(async (response) => {
                if (response.ok) {
                    const json = (await response.json()) as {
                        data: BookingResource[];
                    };
                    setBookings(json.data);
                }
            })
            .catch(() => {
                // Silently fail
            });
    }

    function onPrevious() {
        if (viewMode === 'month') {
            const prev = getPreviousMonth(displayedYear, displayedMonth);

            if (prev.year < minYear) {
                return;
            }

            setDisplayedYear(prev.year);
            setDisplayedMonth(prev.month);
        } else if (viewMode === 'week') {
            const prev = getPreviousWeek(
                displayedYear,
                displayedMonth,
                displayedDay,
            );

            if (prev.year < minYear) {
                return;
            }

            setDisplayedYear(prev.year);
            setDisplayedMonth(prev.month);
            setDisplayedDay(prev.day);
        } else {
            const prev = getPreviousDay(
                displayedYear,
                displayedMonth,
                displayedDay,
            );

            if (prev.year < minYear) {
                return;
            }

            setDisplayedYear(prev.year);
            setDisplayedMonth(prev.month);
            setDisplayedDay(prev.day);
        }
    }

    function onNext() {
        if (viewMode === 'month') {
            const next = getNextMonth(displayedYear, displayedMonth);

            if (next.year > maxYear) {
                return;
            }

            setDisplayedYear(next.year);
            setDisplayedMonth(next.month);
        } else if (viewMode === 'week') {
            const next = getNextWeek(
                displayedYear,
                displayedMonth,
                displayedDay,
            );

            if (next.year > maxYear) {
                return;
            }

            setDisplayedYear(next.year);
            setDisplayedMonth(next.month);
            setDisplayedDay(next.day);
        } else {
            const next = getNextDay(
                displayedYear,
                displayedMonth,
                displayedDay,
            );

            if (next.year > maxYear) {
                return;
            }

            setDisplayedYear(next.year);
            setDisplayedMonth(next.month);
            setDisplayedDay(next.day);
        }
    }

    function onGoToToday() {
        setDisplayedYear(currentYear);
        setDisplayedMonth(currentMonth);
        setDisplayedDay(currentDay);
    }

    function clampDay(year: number, month: number, day: number): number {
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        return Math.min(day, daysInMonth);
    }

    function onSelectMonth(month: number) {
        setDisplayedMonth(month);
        setDisplayedDay((prev) => clampDay(displayedYear, month, prev));
    }

    function onSelectYear(year: number) {
        setDisplayedYear(year);
        setDisplayedDay((prev) => clampDay(year, displayedMonth, prev));
    }

    function onFillerDateClick(year: number, month: number) {
        setDisplayedYear(year);
        setDisplayedMonth(month);
        setDisplayedDay((prev) => clampDay(year, month, prev));
    }

    function handleCreateBooking(initialDate?: string, initialTime?: string) {
        setEditingBooking(undefined);
        setDialogInitialDate(initialDate);
        setDialogInitialTime(initialTime);
        setDialogOpen(true);
    }

    function handleEditBooking(booking: BookingResource) {
        setEditingBooking(booking);
        setDialogInitialDate(undefined);
        setDialogInitialTime(undefined);
        setDialogOpen(true);
    }

    function handleDeleteBooking(booking: BookingResource) {
        setDeletingBooking(booking);
    }

    async function handleDeleteConfirm(scope: DeleteScope) {
        if (!deletingBooking || !currentCongregation?.slug) {
            return;
        }

        const csrfToken =
            document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] ?? '';

        try {
            const url = destroy.url({
                current_congregation: currentCongregation.slug,
                booking: deletingBooking.id,
            });

            const response = await fetch(url, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(csrfToken),
                },
                body: JSON.stringify({ scope }),
            });

            if (response.ok || response.status === 204) {
                toast.success('Booking deleted.');
                setDeletingBooking(null);
                refetchBookings();
            } else if (response.status === 403) {
                toast.error('You do not have permission to delete this booking.');
            } else {
                toast.error('Failed to delete booking.');
            }
        } catch {
            toast.error('Network error. Please try again.');
        }
    }

    function handleDialogOpenChange(open: boolean) {
        setDialogOpen(open);

        if (!open) {
            setEditingBooking(undefined);
            refetchBookings();
        }
    }

    useKeyboardShortcuts(
        {
            arrowleft: onPrevious,
            arrowright: onNext,
        },
        {
            '0': () => setViewMode('month'),
            '1': () => setViewMode('week'),
            '2': () => setViewMode('day'),
        },
    );

    return (
        <ErrorBoundary>
            <Head title="Calendar" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <CalendarHeader
                    viewMode={viewMode}
                    displayedYear={displayedYear}
                    displayedMonth={displayedMonth}
                    displayedDay={displayedDay}
                    onPrevious={onPrevious}
                    onNext={onNext}
                    onSelectMonth={onSelectMonth}
                    onSelectYear={onSelectYear}
                    onGoToToday={onGoToToday}
                    onViewModeChange={setViewMode}
                    isToday={isTodayVisible}
                    onCreateBooking={() => handleCreateBooking()}
                />
                {kingdomHallId && connectionStatus !== 'connected' && (
                    <div className="fixed top-4 right-4 z-50 flex items-center gap-1.5 rounded-md bg-muted px-2.5 py-1 text-xs text-muted-foreground shadow-sm">
                        <WifiOff className="h-3.5 w-3.5" />
                        <span>
                            {connectionStatus === 'connecting'
                                ? 'Reconnecting…'
                                : 'Offline'}
                        </span>
                    </div>
                )}
                <div className="min-h-0 flex-1">
                    {viewMode === 'month' && (
                        <MonthGrid
                            grid={grid}
                            today={today}
                            bookings={bookings}
                            onFillerDateClick={onFillerDateClick}
                            onCreateBooking={handleCreateBooking}
                            onEditBooking={handleEditBooking}
                            onDeleteBooking={handleDeleteBooking}
                            getDragProps={getDragProps}
                            getDropZoneProps={getDropZoneProps}
                            draggedBookingId={dragState.draggedBookingId}
                            draggedBooking={dragState.draggedBooking}
                        />
                    )}
                    {viewMode === 'week' && (
                        <WeekGrid
                            days={weekDays}
                            bookings={bookings}
                            onCreateBooking={handleCreateBooking}
                            onEditBooking={handleEditBooking}
                            onDeleteBooking={handleDeleteBooking}
                            getDragProps={getDragProps}
                            getDropZoneProps={getDropZoneProps}
                            draggedBookingId={dragState.draggedBookingId}
                            draggedBooking={dragState.draggedBooking}
                        />
                    )}
                    {viewMode === 'day' && (
                        <DayGrid
                            date={{
                                day: displayedDay,
                                month: displayedMonth,
                                year: displayedYear,
                            }}
                            rooms={rooms}
                            isToday={isDayToday}
                            bookings={bookings}
                            onCreateBooking={handleCreateBooking}
                            onEditBooking={handleEditBooking}
                            onDeleteBooking={handleDeleteBooking}
                            getDragProps={getDragProps}
                            getDropZoneProps={getDropZoneProps}
                            draggedBookingId={dragState.draggedBookingId}
                            draggedBooking={dragState.draggedBooking}
                        />
                    )}
                </div>
            </div>

            <BookingDialog
                rooms={rooms}
                congregations={
                    congregations.length > 1 ? congregations : undefined
                }
                initialDate={dialogInitialDate}
                initialTime={dialogInitialTime}
                booking={editingBooking}
                open={dialogOpen}
                onOpenChange={handleDialogOpenChange}
            />

            <DeleteConfirmDialog
                open={!!deletingBooking}
                bookingName={deletingBooking?.name ?? ''}
                isRecurring={!!deletingBooking?.recurrence_pattern_id}
                onConfirm={handleDeleteConfirm}
                onCancel={() => setDeletingBooking(null)}
            />

            <RecurrenceEditPrompt
                open={dragScopePromptOpen}
                onSelect={handleDragScopeSelect}
                onCancel={handleDragScopeCancel}
            />
        </ErrorBoundary>
    );
}

Calendar.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Calendar',
            href: props.currentTeam ? calendar(props.currentTeam.slug) : '/',
        },
    ],
});
