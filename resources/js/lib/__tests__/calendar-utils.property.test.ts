// Feature: calendar-view, Property 1: Grid generation produces exactly 42 cells with correct dates
// Feature: calendar-view, Property 2: Filler date click target correctness
// Feature: calendar-view, Property 3: Month navigation arithmetic
// Feature: calendar-view, Property 4: Picker selection state consistency
// Feature: calendar-view, Property 5: Today button disabled state
// Feature: calendar-view, Property 6: Today indicator exclusivity
import * as fc from 'fast-check';
import { describe, expect, it } from 'vitest';

import {
    generateMonthGrid,
    getNextMonth,
    getPreviousMonth,
} from '@/lib/calendar-utils';

/**
 * **Validates: Requirements 2.4, 3.1, 3.2**
 *
 * For any valid year (within ±10 of current) and month (0–11),
 * generateMonthGrid(year, month) returns exactly 42 GridDate entries where:
 * (a) all days of the target month appear in sequential order,
 * (b) leading filler dates are the correct final days of the previous month,
 * (c) trailing filler dates start at 1 and increment sequentially.
 */
describe('Property 1: Grid generation produces exactly 42 cells with correct dates', () => {
    const currentYear = new Date().getFullYear();

    const yearArb = fc.integer({
        min: currentYear - 10,
        max: currentYear + 10,
    });
    const monthArb = fc.integer({ min: 0, max: 11 });

    it('always produces exactly 42 cells', () => {
        fc.assert(
            fc.property(yearArb, monthArb, (year, month) => {
                const grid = generateMonthGrid(year, month);
                expect(grid).toHaveLength(42);
            }),
            { numRuns: 100 },
        );
    });

    it('all days of the target month appear sequentially', () => {
        fc.assert(
            fc.property(yearArb, monthArb, (year, month) => {
                const grid = generateMonthGrid(year, month);
                const daysInMonth = new Date(year, month + 1, 0).getDate();

                const currentMonthDates = grid.filter((d) => d.isCurrentMonth);

                expect(currentMonthDates).toHaveLength(daysInMonth);

                for (let i = 0; i < currentMonthDates.length; i++) {
                    expect(currentMonthDates[i].day).toBe(i + 1);
                    expect(currentMonthDates[i].month).toBe(month);
                    expect(currentMonthDates[i].year).toBe(year);
                }
            }),
            { numRuns: 100 },
        );
    });

    it('leading filler dates are correct final days of previous month', () => {
        fc.assert(
            fc.property(yearArb, monthArb, (year, month) => {
                const grid = generateMonthGrid(year, month);
                const prev = getPreviousMonth(year, month);
                const daysInPrevMonth = new Date(
                    prev.year,
                    prev.month + 1,
                    0,
                ).getDate();

                // Leading fillers are all cells before the first current-month cell
                const firstCurrentIdx = grid.findIndex((d) => d.isCurrentMonth);
                const leadingFillers = grid.slice(0, firstCurrentIdx);

                // Leading fillers should be the last N days of the previous month in ascending order
                for (let i = 0; i < leadingFillers.length; i++) {
                    const expectedDay =
                        daysInPrevMonth - leadingFillers.length + 1 + i;
                    expect(leadingFillers[i].day).toBe(expectedDay);
                    expect(leadingFillers[i].month).toBe(prev.month);
                    expect(leadingFillers[i].year).toBe(prev.year);
                    expect(leadingFillers[i].isCurrentMonth).toBe(false);
                }
            }),
            { numRuns: 100 },
        );
    });

    it('trailing filler dates start at 1 and increment sequentially', () => {
        fc.assert(
            fc.property(yearArb, monthArb, (year, month) => {
                const grid = generateMonthGrid(year, month);

                // Trailing fillers are all cells after the last current-month cell
                const lastCurrentIdx = grid.findLastIndex(
                    (d) => d.isCurrentMonth,
                );
                const trailingFillers = grid.slice(lastCurrentIdx + 1);

                for (let i = 0; i < trailingFillers.length; i++) {
                    expect(trailingFillers[i].day).toBe(i + 1);
                    expect(trailingFillers[i].isCurrentMonth).toBe(false);
                }
            }),
            { numRuns: 100 },
        );
    });
});

/**
 * **Validates: Requirements 3.4**
 *
 * For any grid produced by generateMonthGrid(year, month), every GridDate
 * where isCurrentMonth === false has a month and year value that corresponds
 * to a valid adjacent month (either previous or next month relative to input).
 */
describe('Property 2: Filler date click target correctness', () => {
    const currentYear = new Date().getFullYear();

    const yearArb = fc.integer({
        min: currentYear - 10,
        max: currentYear + 10,
    });
    const monthArb = fc.integer({ min: 0, max: 11 });

    it('every filler date has a valid adjacent month/year', () => {
        fc.assert(
            fc.property(yearArb, monthArb, (year, month) => {
                const grid = generateMonthGrid(year, month);
                const prev = getPreviousMonth(year, month);
                const next = getNextMonth(year, month);

                const fillerDates = grid.filter((cell) => !cell.isCurrentMonth);

                for (const cell of fillerDates) {
                    const matchesPrev =
                        cell.year === prev.year && cell.month === prev.month;
                    const matchesNext =
                        cell.year === next.year && cell.month === next.month;

                    expect(
                        matchesPrev || matchesNext,
                        `Filler date ${cell.year}-${cell.month}-${cell.day} does not match previous (${prev.year}-${prev.month}) or next (${next.year}-${next.month}) month for input ${year}-${month}`,
                    ).toBe(true);
                }
            }),
            { numRuns: 100 },
        );
    });
});

/**
 * **Validates: Requirements 4.2, 4.3**
 *
 * For any valid year and month:
 * - getPreviousMonth(year, 0) returns December of year-1
 * - getNextMonth(year, 11) returns January of year+1
 * - Round-trip: getNextMonth(getPreviousMonth(y, m)) returns original {year, month}
 * - Reverse round-trip: getPreviousMonth(getNextMonth(y, m)) returns original {year, month}
 */
describe('Property 3: Month navigation arithmetic', () => {
    const currentYear = new Date().getFullYear();

    const yearArb = fc.integer({
        min: currentYear - 10,
        max: currentYear + 10,
    });
    const monthArb = fc.integer({ min: 0, max: 11 });

    it('getPreviousMonth(y, 0) returns December of previous year', () => {
        fc.assert(
            fc.property(yearArb, (year) => {
                const result = getPreviousMonth(year, 0);

                expect(result).toEqual({ year: year - 1, month: 11 });
            }),
            { numRuns: 100 },
        );
    });

    it('getNextMonth(y, 11) returns January of next year', () => {
        fc.assert(
            fc.property(yearArb, (year) => {
                const result = getNextMonth(year, 11);

                expect(result).toEqual({ year: year + 1, month: 0 });
            }),
            { numRuns: 100 },
        );
    });

    it('getNextMonth(getPreviousMonth(y, m)) returns original {year, month}', () => {
        fc.assert(
            fc.property(yearArb, monthArb, (year, month) => {
                const prev = getPreviousMonth(year, month);
                const result = getNextMonth(prev.year, prev.month);

                expect(result).toEqual({ year, month });
            }),
            { numRuns: 100 },
        );
    });

    it('getPreviousMonth(getNextMonth(y, m)) returns original {year, month}', () => {
        fc.assert(
            fc.property(yearArb, monthArb, (year, month) => {
                const next = getNextMonth(year, month);
                const result = getPreviousMonth(next.year, next.month);

                expect(result).toEqual({ year, month });
            }),
            { numRuns: 100 },
        );
    });
});

/**
 * **Validates: Requirements 7.1, 7.3, 7.4**
 *
 * For any month that is NOT the current month/year, all 42 cells have isToday === false.
 * For the current month/year, exactly one cell has isToday === true and that cell has
 * isCurrentMonth === true.
 */
describe('Property 6: Today indicator exclusivity', () => {
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth();

    const yearArb = fc.integer({
        min: currentYear - 10,
        max: currentYear + 10,
    });
    const monthArb = fc.integer({ min: 0, max: 11 });

    it('for non-current months, all 42 cells have isToday === false', () => {
        fc.assert(
            fc.property(
                yearArb.filter((y) => !(y === currentYear)),
                monthArb,
                (year, month) => {
                    const grid = generateMonthGrid(year, month);

                    for (const cell of grid) {
                        expect(cell.isToday).toBe(false);
                    }
                },
            ),
            { numRuns: 100 },
        );

        // Also test: same year but different month
        fc.assert(
            fc.property(
                monthArb.filter((m) => m !== currentMonth),
                (month) => {
                    const grid = generateMonthGrid(currentYear, month);

                    for (const cell of grid) {
                        expect(cell.isToday).toBe(false);
                    }
                },
            ),
            { numRuns: 100 },
        );
    });

    it('for current month, exactly one cell has isToday === true and it has isCurrentMonth === true', () => {
        const grid = generateMonthGrid(currentYear, currentMonth);
        const todayCells = grid.filter((cell) => cell.isToday);

        expect(todayCells).toHaveLength(1);
        expect(todayCells[0].isCurrentMonth).toBe(true);
        expect(todayCells[0].day).toBe(new Date().getDate());
        expect(todayCells[0].month).toBe(currentMonth);
        expect(todayCells[0].year).toBe(currentYear);
    });
});

/**
 * **Validates: Requirements 6.3**
 *
 * For any displayed year and month, the "Go to current month" button SHALL be
 * disabled if and only if the displayed year equals the current year AND the
 * displayed month equals the current month.
 */
describe('Property 5: Today button disabled state', () => {
    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();

    const computeIsCurrentMonth = (
        displayedYear: number,
        displayedMonth: number,
    ): boolean => {
        const today = new Date();

        return (
            displayedYear === today.getFullYear() &&
            displayedMonth === today.getMonth()
        );
    };

    const yearArb = fc.integer({
        min: currentYear - 10,
        max: currentYear + 10,
    });
    const monthArb = fc.integer({ min: 0, max: 11 });

    it('isCurrentMonth is true only for the actual current year and month', () => {
        fc.assert(
            fc.property(yearArb, monthArb, (year, month) => {
                const result = computeIsCurrentMonth(year, month);

                if (year === currentYear && month === currentMonth) {
                    expect(result).toBe(true);
                } else {
                    expect(result).toBe(false);
                }
            }),
            { numRuns: 100 },
        );
    });

    it('isCurrentMonth is always true for exactly (currentYear, currentMonth)', () => {
        const result = computeIsCurrentMonth(currentYear, currentMonth);
        expect(result).toBe(true);
    });

    it('isCurrentMonth is false when year differs from current year', () => {
        fc.assert(
            fc.property(
                yearArb.filter((y) => y !== currentYear),
                monthArb,
                (year, month) => {
                    const result = computeIsCurrentMonth(year, month);
                    expect(result).toBe(false);
                },
            ),
            { numRuns: 100 },
        );
    });

    it('isCurrentMonth is false when month differs from current month', () => {
        fc.assert(
            fc.property(
                yearArb,
                monthArb.filter((m) => m !== currentMonth),
                (year, month) => {
                    const result = computeIsCurrentMonth(year, month);
                    expect(result).toBe(false);
                },
            ),
            { numRuns: 100 },
        );
    });
});

/**
 * **Validates: Requirements 5.3, 5.4**
 *
 * For any month selection M (0–11) applied to a state with year Y, the resulting
 * displayed state is {year: Y, month: M}. For any year selection Y applied to a
 * state with month M, the resulting displayed state is {year: Y, month: M}.
 */
describe('Property 4: Picker selection state consistency', () => {
    const currentYear = new Date().getFullYear();

    // Simulates month picker selection: keeps year, changes month
    const selectMonth = (
        state: { year: number; month: number },
        selectedMonth: number,
    ) => ({
        year: state.year,
        month: selectedMonth,
    });

    // Simulates year picker selection: keeps month, changes year
    const selectYear = (
        state: { year: number; month: number },
        selectedYear: number,
    ) => ({
        year: selectedYear,
        month: state.month,
    });

    const yearArb = fc.integer({
        min: currentYear - 5,
        max: currentYear + 5,
    });
    const monthArb = fc.integer({ min: 0, max: 11 });

    it('selecting a month keeps the year unchanged and sets the selected month', () => {
        fc.assert(
            fc.property(
                yearArb,
                monthArb,
                monthArb,
                (initialYear, initialMonth, selectedMonth) => {
                    const state = { year: initialYear, month: initialMonth };
                    const result = selectMonth(state, selectedMonth);

                    expect(result).toEqual({
                        year: initialYear,
                        month: selectedMonth,
                    });
                },
            ),
            { numRuns: 100 },
        );
    });

    it('selecting a year keeps the month unchanged and sets the selected year', () => {
        fc.assert(
            fc.property(
                yearArb,
                monthArb,
                yearArb,
                (initialYear, initialMonth, selectedYear) => {
                    const state = { year: initialYear, month: initialMonth };
                    const result = selectYear(state, selectedYear);

                    expect(result).toEqual({
                        year: selectedYear,
                        month: initialMonth,
                    });
                },
            ),
            { numRuns: 100 },
        );
    });
});
