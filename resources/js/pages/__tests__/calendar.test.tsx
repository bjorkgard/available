import '@testing-library/jest-dom/vitest';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

// Mock @inertiajs/react to provide a no-op Head component
vi.mock('@inertiajs/react', () => ({
    Head: ({ children }: { children?: React.ReactNode }) => <>{children}</>,
    usePage: () => ({ props: { currentCongregation: null } }),
}));

// Mock @/routes to provide a stub calendar function
vi.mock('@/routes', () => ({
    calendar: () => ({ url: '/test/calendar', method: 'get' as const }),
}));

import Calendar from '@/pages/calendar';

afterEach(() => {
    cleanup();
    vi.useRealTimers();
});

describe('Calendar page state logic', () => {
    describe('initial state is current month/year', () => {
        it('displays the current month name in the month selector', () => {
            render(<Calendar />);

            const now = new Date();
            const currentMonthName = new Intl.DateTimeFormat(undefined, {
                month: 'long',
            }).format(now);

            expect(screen.getByText(currentMonthName)).toBeInTheDocument();
        });

        it('displays the current year in the year selector', () => {
            render(<Calendar />);

            const currentYear = new Date().getFullYear();

            expect(screen.getByText(String(currentYear))).toBeInTheDocument();
        });

        it('renders the Today button as disabled on initial load', () => {
            render(<Calendar />);

            const todayButton = screen.getByRole('button', { name: /today/i });

            expect(todayButton).toBeDisabled();
        });
    });

    describe('navigation beyond ±10 year bounds is clamped', () => {
        it('does not navigate before January of minYear', () => {
            // Set fake date to February of minYear+10 (i.e. Feb of current year)
            // so we have a short distance to navigate
            vi.useFakeTimers();
            vi.setSystemTime(new Date(2025, 1, 15)); // Feb 15, 2025

            render(<Calendar />);

            const prevButton = screen.getByRole('button', {
                name: /previous month/i,
            });

            // minYear = 2025 - 10 = 2015
            // From Feb 2025, navigate to Jan 2015 = 121 months back
            // Navigate 13 months back to reach Jan 2024 first as a sanity check
            for (let i = 0; i < 13; i++) {
                fireEvent.click(prevButton);
            }

            // Should be at Jan 2024
            const januaryName = new Intl.DateTimeFormat(undefined, {
                month: 'long',
            }).format(new Date(2025, 0, 1));
            expect(screen.getByText(januaryName)).toBeInTheDocument();

            // Now navigate the rest to reach Jan 2015 (108 more months)
            for (let i = 0; i < 108; i++) {
                fireEvent.click(prevButton);
            }

            // Should be at Jan 2015, the boundary
            expect(screen.getByText(januaryName)).toBeInTheDocument();

            // Click one more time — should stay clamped at Jan 2015
            fireEvent.click(prevButton);
            expect(screen.getByText(januaryName)).toBeInTheDocument();
        });

        it('does not navigate past December of maxYear', () => {
            vi.useFakeTimers();
            vi.setSystemTime(new Date(2025, 10, 15)); // Nov 15, 2025

            render(<Calendar />);

            const nextButton = screen.getByRole('button', {
                name: /next month/i,
            });

            // maxYear = 2025 + 10 = 2035
            // From Nov 2025, navigate to Dec 2035 = 121 months forward
            for (let i = 0; i < 121; i++) {
                fireEvent.click(nextButton);
            }

            // Should be at Dec 2035
            const decemberName = new Intl.DateTimeFormat(undefined, {
                month: 'long',
            }).format(new Date(2025, 11, 1));
            expect(screen.getByText(decemberName)).toBeInTheDocument();

            // Click one more time — should stay clamped at Dec 2035
            fireEvent.click(nextButton);
            expect(screen.getByText(decemberName)).toBeInTheDocument();
        });
    });

    describe('filler date click navigates to correct month', () => {
        it('clicking a leading filler date navigates to the previous month', () => {
            // Use a date where we know the first day isn't Sunday
            // March 2025 starts on Saturday — 6 leading filler days
            vi.useFakeTimers();
            vi.setSystemTime(new Date(2025, 2, 15)); // Mar 15, 2025

            render(<Calendar />);

            // Find filler date buttons (they are buttons with muted text)
            const fillerButtons = screen
                .getAllByRole('button')
                .filter(
                    (btn) =>
                        btn.classList.contains('text-muted-foreground') &&
                        !isNaN(Number(btn.textContent)),
                );

            expect(fillerButtons.length).toBeGreaterThan(0);

            // The first filler button should be a leading filler from Feb 2025
            // March 2025 starts on Saturday (day index 6), so leading fillers are Feb 23-28
            fireEvent.click(fillerButtons[0]);

            // After clicking, should navigate to February
            const februaryName = new Intl.DateTimeFormat(undefined, {
                month: 'long',
            }).format(new Date(2025, 1, 1));
            expect(screen.getByText(februaryName)).toBeInTheDocument();
        });

        it('clicking a trailing filler date navigates to the next month', () => {
            // March 2025: 31 days, starts Saturday (6 leading fillers)
            // Grid: 6 fillers + 31 days = 37 cells used, 5 trailing fillers (April 1-5)
            vi.useFakeTimers();
            vi.setSystemTime(new Date(2025, 2, 15)); // Mar 15, 2025

            render(<Calendar />);

            // Find all filler date buttons
            const fillerButtons = screen
                .getAllByRole('button')
                .filter(
                    (btn) =>
                        btn.classList.contains('text-muted-foreground') &&
                        !isNaN(Number(btn.textContent)),
                );

            // The last filler button should be a trailing filler from April
            const lastFiller = fillerButtons[fillerButtons.length - 1];
            fireEvent.click(lastFiller);

            // After clicking, should navigate to April
            const aprilName = new Intl.DateTimeFormat(undefined, {
                month: 'long',
            }).format(new Date(2025, 3, 1));
            expect(screen.getByText(aprilName)).toBeInTheDocument();
        });
    });

    describe('"Today" resets to current month', () => {
        it('clicking Today returns to the current month after navigating forward', () => {
            render(<Calendar />);

            const now = new Date();
            const currentMonthName = new Intl.DateTimeFormat(undefined, {
                month: 'long',
            }).format(now);
            const currentYear = now.getFullYear();

            // Navigate forward 3 months
            const nextButton = screen.getByRole('button', {
                name: /next month/i,
            });
            fireEvent.click(nextButton);
            fireEvent.click(nextButton);
            fireEvent.click(nextButton);

            // The Today button should now be enabled
            const todayButton = screen.getByRole('button', {
                name: /today/i,
            });
            expect(todayButton).not.toBeDisabled();

            // Click Today
            fireEvent.click(todayButton);

            // Verify we're back to the current month and year
            expect(screen.getByText(currentMonthName)).toBeInTheDocument();
            expect(screen.getByText(String(currentYear))).toBeInTheDocument();

            // Today button should be disabled again
            expect(todayButton).toBeDisabled();
        });

        it('clicking Today returns to current month after navigating backward', () => {
            render(<Calendar />);

            const now = new Date();
            const currentMonthName = new Intl.DateTimeFormat(undefined, {
                month: 'long',
            }).format(now);

            // Navigate backward 2 months
            const prevButton = screen.getByRole('button', {
                name: /previous month/i,
            });
            fireEvent.click(prevButton);
            fireEvent.click(prevButton);

            // The Today button should be enabled
            const todayButton = screen.getByRole('button', {
                name: /today/i,
            });
            expect(todayButton).not.toBeDisabled();

            // Click Today
            fireEvent.click(todayButton);

            // Verify current month name is displayed
            expect(screen.getByText(currentMonthName)).toBeInTheDocument();

            // Today button should be disabled again
            expect(todayButton).toBeDisabled();
        });
    });
});
