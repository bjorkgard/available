import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuShortcut,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type ViewMode = 'month' | 'week' | 'day';

interface CalendarHeaderProps {
    viewMode: ViewMode;
    displayedYear: number;
    displayedMonth: number;
    displayedDay: number;
    onPrevious: () => void;
    onNext: () => void;
    onSelectMonth: (month: number) => void;
    onSelectYear: (year: number) => void;
    onGoToToday: () => void;
    onViewModeChange: (mode: ViewMode) => void;
    isToday: boolean;
}

function getMonthNames(): string[] {
    const formatter = new Intl.DateTimeFormat(undefined, { month: 'long' });

    return Array.from({ length: 12 }, (_, i) =>
        formatter.format(new Date(2025, i, 1)),
    );
}

function getYearRange(): number[] {
    const currentYear = new Date().getFullYear();

    return Array.from({ length: 11 }, (_, i) => currentYear - 5 + i);
}

function getNavigationLabel(viewMode: ViewMode): {
    previous: string;
    next: string;
} {
    switch (viewMode) {
        case 'month':
            return { previous: 'Previous month', next: 'Next month' };
        case 'week':
            return { previous: 'Previous week', next: 'Next week' };
        case 'day':
            return { previous: 'Previous day', next: 'Next day' };
    }
}

function formatDayContext(year: number, month: number, day: number): string {
    const formatter = new Intl.DateTimeFormat(undefined, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });

    return formatter.format(new Date(year, month, day));
}

function formatWeekContext(year: number, month: number, day: number): string {
    const start = new Date(year, month, day);
    const dayOfWeek = start.getDay();
    const weekStart = new Date(start);
    weekStart.setDate(start.getDate() - dayOfWeek);
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);

    const formatter = new Intl.DateTimeFormat(undefined, {
        day: 'numeric',
        month: 'short',
    });

    return `${formatter.format(weekStart)} – ${formatter.format(weekEnd)}`;
}

export function CalendarHeader({
    viewMode,
    displayedYear,
    displayedMonth,
    displayedDay,
    onPrevious,
    onNext,
    onSelectMonth,
    onSelectYear,
    onGoToToday,
    onViewModeChange,
    isToday,
}: CalendarHeaderProps) {
    const monthNames = getMonthNames();
    const years = getYearRange();
    const navLabels = getNavigationLabel(viewMode);

    return (
        <div className="flex items-center gap-2">
            <Button
                variant="outline"
                size="icon"
                onClick={onPrevious}
                aria-label={navLabels.previous}
            >
                <ChevronLeft />
            </Button>

            <Select
                value={String(displayedMonth)}
                onValueChange={(value) => onSelectMonth(Number(value))}
            >
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {monthNames.map((name, index) => (
                        <SelectItem key={index} value={String(index)}>
                            {name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <Select
                value={String(displayedYear)}
                onValueChange={(value) => onSelectYear(Number(value))}
            >
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {years.map((year) => (
                        <SelectItem key={year} value={String(year)}>
                            {year}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <Button
                variant="outline"
                size="icon"
                onClick={onNext}
                aria-label={navLabels.next}
            >
                <ChevronRight />
            </Button>

            <Button
                variant="outline"
                onClick={onGoToToday}
                disabled={isToday}
                aria-disabled={isToday}
            >
                Today
            </Button>

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="icon" aria-label="View mode">
                        <CalendarDays />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start">
                    <DropdownMenuLabel>View</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuRadioGroup
                        value={viewMode}
                        onValueChange={(value) =>
                            onViewModeChange(value as ViewMode)
                        }
                    >
                        <DropdownMenuRadioItem value="month">
                            Month
                            <DropdownMenuShortcut>⌘0</DropdownMenuShortcut>
                        </DropdownMenuRadioItem>
                        <DropdownMenuRadioItem value="week">
                            Week
                            <DropdownMenuShortcut>⌘1</DropdownMenuShortcut>
                        </DropdownMenuRadioItem>
                        <DropdownMenuRadioItem value="day">
                            Day
                            <DropdownMenuShortcut>⌘2</DropdownMenuShortcut>
                        </DropdownMenuRadioItem>
                    </DropdownMenuRadioGroup>
                </DropdownMenuContent>
            </DropdownMenu>

            {viewMode === 'week' && (
                <span className="ml-2 text-sm text-muted-foreground">
                    {formatWeekContext(
                        displayedYear,
                        displayedMonth,
                        displayedDay,
                    )}
                </span>
            )}

            {viewMode === 'day' && (
                <span className="ml-2 text-sm text-muted-foreground">
                    {formatDayContext(
                        displayedYear,
                        displayedMonth,
                        displayedDay,
                    )}
                </span>
            )}
        </div>
    );
}
