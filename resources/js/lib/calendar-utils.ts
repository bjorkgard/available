/**
 * Pure date arithmetic utilities for the calendar view.
 * No React dependencies — fully testable in isolation.
 */

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

    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const prev = getPreviousMonth(year, month);
    const daysInPrevMonth = new Date(prev.year, prev.month + 1, 0).getDate();

    const next = getNextMonth(year, month);

    const grid: GridDate[] = [];

    // Leading filler dates from previous month
    for (let i = firstWeekday - 1; i >= 0; i--) {
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
        const loc = new Intl.Locale(locale ?? navigator.language);

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
    const resolvedLocale = locale ?? navigator.language;

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

    const formatter = new Intl.DateTimeFormat(locale ?? navigator.language, {
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
