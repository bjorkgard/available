import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { CalendarHeader } from '@/components/calendar-header';
import type { ViewMode } from '@/components/calendar-header';
import { DayGrid } from '@/components/day-grid';
import { MonthGrid } from '@/components/month-grid';
import { WeekGrid } from '@/components/week-grid';
import { useKeyboardShortcuts } from '@/hooks/use-keyboard-shortcuts';
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
import type { KingdomHall, Room } from '@/types';

function getInitialViewMode(): ViewMode {
    if (typeof window === 'undefined') {
        return 'month';
    }

    const width = window.innerWidth;

    if (width < 768) {
        return 'day';
    }

    if (width < 1024) {
        return 'week';
    }

    return 'month';
}

export default function Calendar() {
    const { currentCongregation } = usePage<{
        currentCongregation?: { slug: string; kingdom_hall?: KingdomHall };
    }>().props;

    const rooms: Room[] =
        currentCongregation?.kingdom_hall?.rooms ?? [];

    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();
    const currentDay = now.getDate();

    const [viewMode, setViewMode] = useState<ViewMode>(getInitialViewMode);
    const [displayedYear, setDisplayedYear] = useState(currentYear);
    const [displayedMonth, setDisplayedMonth] = useState(currentMonth);
    const [displayedDay, setDisplayedDay] = useState(currentDay);

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

    function onSelectMonth(month: number) {
        setDisplayedMonth(month);
    }

    function onSelectYear(year: number) {
        setDisplayedYear(year);
    }

    function onFillerDateClick(year: number, month: number) {
        setDisplayedYear(year);
        setDisplayedMonth(month);
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
        <>
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
                />
                <div className="min-h-0 flex-1">
                    {viewMode === 'month' && (
                        <MonthGrid
                            grid={grid}
                            today={today}
                            onFillerDateClick={onFillerDateClick}
                        />
                    )}
                    {viewMode === 'week' && <WeekGrid days={weekDays} />}
                    {viewMode === 'day' && (
                        <DayGrid
                            date={{
                                day: displayedDay,
                                month: displayedMonth,
                                year: displayedYear,
                            }}
                            rooms={rooms}
                            isToday={isDayToday}
                        />
                    )}
                </div>
            </div>
        </>
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
