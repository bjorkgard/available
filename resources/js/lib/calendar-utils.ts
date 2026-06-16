/**
 * Pure date arithmetic utilities for the calendar view.
 * No React dependencies — fully testable in isolation.
 */

import { getAppLocale } from '@/lib/locale';

export interface GridDate {
    day: number;
    month: number;
    year: number;
    isCurrentMonth: boolean;
    isToday: boolean;
}

export interface DateInfo {
    day: number;
    month: number;
    year: number;
}

/**
 * Generates a 42-element array representing 6 complete weeks for the given month.
 * Includes leading filler dates from previous month and trailing filler dates from next month.
 *
 * @param year - The full year (e.g. 2025)
 * @param month - 0-indexed month (0 = January, 11 = December)
 */
export function generateMonthGrid(year: number, month: number): GridDate[] {
    const today = new Date();
    const todayDay = today.getDate();
    const todayMonth = today.getMonth();
    const todayYear = today.getFullYear();

    const firstDayOfMonth = new Date(year, month, 1);
    const firstWeekday = firstDayOfMonth.getDay(); // 0 = Sunday

    // Offset relative to locale's first day of week
    const localeFirstDay = getFirstDayOfWeek();
    const leadingDays = (firstWeekday - localeFirstDay + 7) % 7;

    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const prev = getPreviousMonth(year, month);
    const daysInPrevMonth = new Date(prev.year, prev.month + 1, 0).getDate();

    const next = getNextMonth(year, month);

    const grid: GridDate[] = [];

    // Leading filler dates from previous month
    for (let i = leadingDays - 1; i >= 0; i--) {
        grid.push({
            day: daysInPrevMonth - i,
            month: prev.month,
            year: prev.year,
            isCurrentMonth: false,
            isToday: false,
        });
    }

    // Days of the current month
    for (let day = 1; day <= daysInMonth; day++) {
        grid.push({
            day,
            month,
            year,
            isCurrentMonth: true,
            isToday:
                day === todayDay && month === todayMonth && year === todayYear,
        });
    }

    // Trailing filler dates from next month
    let trailingDay = 1;

    while (grid.length < 42) {
        grid.push({
            day: trailingDay,
            month: next.month,
            year: next.year,
            isCurrentMonth: false,
            isToday: false,
        });
        trailingDay++;
    }

    return grid;
}

/**
 * Returns the previous month/year, handling January → December rollover.
 *
 * @param year - The full year
 * @param month - 0-indexed month (0 = January, 11 = December)
 */
export function getPreviousMonth(
    year: number,
    month: number,
): { year: number; month: number } {
    if (month === 0) {
        return { year: year - 1, month: 11 };
    }

    return { year, month: month - 1 };
}

/**
 * Returns the next month/year, handling December → January rollover.
 *
 * @param year - The full year
 * @param month - 0-indexed month (0 = January, 11 = December)
 */
export function getNextMonth(
    year: number,
    month: number,
): { year: number; month: number } {
    if (month === 11) {
        return { year: year + 1, month: 0 };
    }

    return { year, month: month + 1 };
}

/**
 * Determines the first day of the week for the given locale (0=Sunday, 1=Monday, etc.).
 * Falls back to Sunday (0) if locale detection fails.
 *
 * @param locale - BCP 47 locale string (e.g. "en-US", "fr-FR")
 */
export function getFirstDayOfWeek(locale?: string): number {
    try {
        const loc = new Intl.Locale(locale ?? getAppLocale());

        // Use weekInfo if available (modern browsers)
        if ('weekInfo' in loc && loc.weekInfo) {
            const weekInfo = loc.weekInfo as { firstDay: number };

            // Intl.Locale weekInfo uses 1=Monday...7=Sunday
            // Convert to 0=Sunday, 1=Monday...6=Saturday
            return weekInfo.firstDay === 7 ? 0 : weekInfo.firstDay;
        }

        // Fallback: try getWeekInfo (older spec name)
        if (
            'getWeekInfo' in loc &&
            typeof (loc as Record<string, unknown>).getWeekInfo === 'function'
        ) {
            const weekInfo = (
                loc as { getWeekInfo: () => { firstDay: number } }
            ).getWeekInfo();

            return weekInfo.firstDay === 7 ? 0 : weekInfo.firstDay;
        }
    } catch {
        // Fall through to default
    }

    return 0; // Default to Sunday
}

/**
 * Returns locale-aware abbreviated weekday names starting from the locale's first day of week.
 * Falls back to English Sunday-start names if locale detection fails.
 *
 * @param locale - BCP 47 locale string (e.g. "en-US", "fr-FR")
 */
export function getWeekdayNames(locale?: string): string[] {
    const firstDay = getFirstDayOfWeek(locale);
    const resolvedLocale = locale ?? getAppLocale();

    try {
        const formatter = new Intl.DateTimeFormat(resolvedLocale, {
            weekday: 'short',
        });

        // Generate names for all 7 days starting from a known Sunday (Jan 4, 2015 is a Sunday)
        const baseSunday = new Date(2015, 0, 4);
        const names: string[] = [];

        for (let i = 0; i < 7; i++) {
            const date = new Date(baseSunday);
            date.setDate(baseSunday.getDate() + i);
            names.push(formatter.format(date));
        }

        // Rotate so the first day of the week is at index 0
        const rotated = [...names.slice(firstDay), ...names.slice(0, firstDay)];

        return rotated;
    } catch {
        // Fallback to English abbreviations
        const fallback = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        return [...fallback.slice(firstDay), ...fallback.slice(0, firstDay)];
    }
}

/**
 * Information about a single day in a week view.
 */
export interface WeekDay {
    day: number;
    month: number;
    year: number;
    label: string;
    isToday: boolean;
}

/**
 * Returns the 7 days of the week containing the given date.
 * Week starts on the locale's first day of week (Sunday by default).
 *
 * @param year - The full year
 * @param month - 0-indexed month
 * @param day - Day of month
 * @param locale - Optional BCP 47 locale string
 */
export function getWeekDays(
    year: number,
    month: number,
    day: number,
    locale?: string,
): WeekDay[] {
    const firstDay = getFirstDayOfWeek(locale);
    const target = new Date(year, month, day);
    const targetWeekday = target.getDay(); // 0=Sunday

    // Calculate offset to the start of the week
    const offset = (targetWeekday - firstDay + 7) % 7;
    const weekStart = new Date(target);
    weekStart.setDate(target.getDate() - offset);

    const today = new Date();
    const todayDay = today.getDate();
    const todayMonth = today.getMonth();
    const todayYear = today.getFullYear();

    const formatter = new Intl.DateTimeFormat(locale ?? getAppLocale(), {
        weekday: 'short',
    });

    const days: WeekDay[] = [];

    for (let i = 0; i < 7; i++) {
        const date = new Date(weekStart);
        date.setDate(weekStart.getDate() + i);

        days.push({
            day: date.getDate(),
            month: date.getMonth(),
            year: date.getFullYear(),
            label: formatter.format(date),
            isToday:
                date.getDate() === todayDay &&
                date.getMonth() === todayMonth &&
                date.getFullYear() === todayYear,
        });
    }

    return days;
}

/**
 * Advances one week from the given date.
 */
export function getNextWeek(
    year: number,
    month: number,
    day: number,
): { year: number; month: number; day: number } {
    const date = new Date(year, month, day + 7);

    return {
        year: date.getFullYear(),
        month: date.getMonth(),
        day: date.getDate(),
    };
}

/**
 * Goes back one week from the given date.
 */
export function getPreviousWeek(
    year: number,
    month: number,
    day: number,
): { year: number; month: number; day: number } {
    const date = new Date(year, month, day - 7);

    return {
        year: date.getFullYear(),
        month: date.getMonth(),
        day: date.getDate(),
    };
}

/**
 * Advances one day from the given date.
 */
export function getNextDay(
    year: number,
    month: number,
    day: number,
): { year: number; month: number; day: number } {
    const date = new Date(year, month, day + 1);

    return {
        year: date.getFullYear(),
        month: date.getMonth(),
        day: date.getDate(),
    };
}

/**
 * Goes back one day from the given date.
 */
export function getPreviousDay(
    year: number,
    month: number,
    day: number,
): { year: number; month: number; day: number } {
    const date = new Date(year, month, day - 1);

    return {
        year: date.getFullYear(),
        month: date.getMonth(),
        day: date.getDate(),
    };
}

// ─── Grid Rendering Utilities ────────────────────────────────────────────────

/** Two-hour intervals for week/day grid hour labels (0, 2, 4, ..., 22). */
export const GRID_HOURS = Array.from({ length: 12 }, (_, i) => i * 2);

/**
 * Formats an hour number as "HH:00".
 */
export function formatHour(hour: number): string {
    return `${String(hour).padStart(2, '0')}:00`;
}

/**
 * Formats a date (0-indexed month) as an ISO-style "YYYY-MM-DD" string.
 */
export function formatDateString(
    year: number,
    month: number,
    day: number,
): string {
    const m = (month + 1).toString().padStart(2, '0');
    const d = day.toString().padStart(2, '0');

    return `${year}-${m}-${d}`;
}

/**
 * Converts a total-minutes value to "HH:MM" string.
 */
export function formatTimeFromMinutes(minutes: number): string {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;

    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
}

/**
 * Returns bookings that overlap a given day.
 */
export function getBookingsForDay<
    T extends { starts_at: string; ends_at: string },
>(bookings: T[], year: number, month: number, day: number): T[] {
    const dayStart = new Date(year, month, day);
    const dayEnd = new Date(year, month, day + 1);

    return bookings.filter((b) => {
        const startsAt = new Date(b.starts_at);
        const endsAt = new Date(b.ends_at);

        return startsAt < dayEnd && endsAt > dayStart;
    });
}

/**
 * Computes overlapping groups for side-by-side rendering of bookings.
 * Returns a map from booking ID to its column index and total columns in that group.
 */
export function computeOverlapLayout<
    T extends { id: string; starts_at: string; ends_at: string },
>(bookings: T[]): Map<string, { column: number; totalColumns: number }> {
    const layout = new Map<string, { column: number; totalColumns: number }>();

    if (bookings.length === 0) {
        return layout;
    }

    const sorted = [...bookings].sort(
        (a, b) =>
            new Date(a.starts_at).getTime() - new Date(b.starts_at).getTime(),
    );

    const groups: T[][] = [];
    let currentGroup: T[] = [];
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

/**
 * Computes the top offset and height (as percentages of the day container)
 * for a booking in week/day views based on a 24-hour day.
 */
export function computeGridPosition(startsAt: string, endsAt: string) {
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
export function getDurationMinutes(startsAt: string, endsAt: string): number {
    const start = new Date(startsAt);
    const end = new Date(endsAt);

    return (end.getTime() - start.getTime()) / 60000;
}

/**
 * Locale-aware month names (12 entries).
 */
export function getMonthNames(locale?: string): string[] {
    const formatter = new Intl.DateTimeFormat(locale ?? getAppLocale(), {
        month: 'long',
    });

    return Array.from({ length: 12 }, (_, i) =>
        formatter.format(new Date(2025, i, 1)),
    );
}

/**
 * Returns an 11-year range centered on the current year.
 */
export function getYearRange(): number[] {
    const currentYear = new Date().getFullYear();

    return Array.from({ length: 11 }, (_, i) => currentYear - 5 + i);
}

/**
 * Formats a date as "weekday, day month" (e.g. "tisdag 3 juni").
 */
export function formatDayContext(
    year: number,
    month: number,
    day: number,
    locale?: string,
): string {
    const formatter = new Intl.DateTimeFormat(locale ?? getAppLocale(), {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });

    return formatter.format(new Date(year, month, day));
}

/**
 * Formats a week range as "day month – day month" (e.g. "2 jun – 8 jun").
 */
export function formatWeekContext(
    year: number,
    month: number,
    day: number,
    locale?: string,
): string {
    const start = new Date(year, month, day);
    const dayOfWeek = start.getDay();
    const weekStart = new Date(start);
    weekStart.setDate(start.getDate() - dayOfWeek);
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);

    const formatter = new Intl.DateTimeFormat(locale ?? getAppLocale(), {
        day: 'numeric',
        month: 'short',
    });

    return `${formatter.format(weekStart)} – ${formatter.format(weekEnd)}`;
}
