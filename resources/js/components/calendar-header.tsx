import { CalendarDays, ChevronLeft, ChevronRight, Plus } from 'lucide-react';
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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { APP_LOCALE } from '@/lib/locale';

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
    onCreateBooking?: () => void;
}

function getMonthNames(): string[] {
    const formatter = new Intl.DateTimeFormat(APP_LOCALE, { month: 'long' });

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
            return { previous: 'Föregående månad', next: 'Nästa månad' };
        case 'week':
            return { previous: 'Föregående vecka', next: 'Nästa vecka' };
        case 'day':
            return { previous: 'Föregående dag', next: 'Nästa dag' };
    }
}

function getNavigationShortcut(viewMode: ViewMode): {
    previous: string;
    next: string;
} {
    switch (viewMode) {
        case 'month':
            return { previous: '←', next: '→' };
        case 'week':
            return { previous: '←', next: '→' };
        case 'day':
            return { previous: '←', next: '→' };
    }
}

function formatDayContext(year: number, month: number, day: number): string {
    const formatter = new Intl.DateTimeFormat(APP_LOCALE, {
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

    const formatter = new Intl.DateTimeFormat(APP_LOCALE, {
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
    onCreateBooking,
}: CalendarHeaderProps) {
    const monthNames = getMonthNames();
    const years = getYearRange();
    const navLabels = getNavigationLabel(viewMode);
    const navShortcuts = getNavigationShortcut(viewMode);

    return (
        <div className="flex items-center gap-2">
            <Tooltip>
                <TooltipTrigger asChild>
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={onPrevious}
                        aria-label={navLabels.previous}
                    >
                        <ChevronLeft />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>
                    <p>
                        {navLabels.previous}{' '}
                        <kbd className="ml-1 rounded bg-muted px-1 py-0.5 text-xs">
                            {navShortcuts.previous}
                        </kbd>
                    </p>
                </TooltipContent>
            </Tooltip>

            <Select
                value={String(displayedMonth)}
                onValueChange={(value) => onSelectMonth(Number(value))}
            >
                <SelectTrigger aria-label="Välj månad">
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
                <SelectTrigger aria-label="Välj år">
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

            <Tooltip>
                <TooltipTrigger asChild>
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={onNext}
                        aria-label={navLabels.next}
                    >
                        <ChevronRight />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>
                    <p>
                        {navLabels.next}{' '}
                        <kbd className="ml-1 rounded bg-muted px-1 py-0.5 text-xs">
                            {navShortcuts.next}
                        </kbd>
                    </p>
                </TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger asChild>
                    <Button
                        variant="outline"
                        onClick={onGoToToday}
                        disabled={isToday}
                        aria-disabled={isToday}
                    >
                        Idag
                    </Button>
                </TooltipTrigger>
                <TooltipContent>
                    <p>Gå till idag</p>
                </TooltipContent>
            </Tooltip>

            <Tooltip>
                <DropdownMenu>
                    <TooltipTrigger asChild>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="outline"
                                size="icon"
                                aria-label="Byt vy"
                            >
                                <CalendarDays />
                            </Button>
                        </DropdownMenuTrigger>
                    </TooltipTrigger>
                    <DropdownMenuContent align="start">
                        <DropdownMenuLabel>Vy</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuRadioGroup
                            value={viewMode}
                            onValueChange={(value) =>
                                onViewModeChange(value as ViewMode)
                            }
                        >
                            <DropdownMenuRadioItem value="month">
                                Månad
                                <DropdownMenuShortcut>⌘0</DropdownMenuShortcut>
                            </DropdownMenuRadioItem>
                            <DropdownMenuRadioItem value="week">
                                Vecka
                                <DropdownMenuShortcut>⌘1</DropdownMenuShortcut>
                            </DropdownMenuRadioItem>
                            <DropdownMenuRadioItem value="day">
                                Dag
                                <DropdownMenuShortcut>⌘2</DropdownMenuShortcut>
                            </DropdownMenuRadioItem>
                        </DropdownMenuRadioGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
                <TooltipContent>
                    <p>Byt vy</p>
                </TooltipContent>
            </Tooltip>

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

            {onCreateBooking && (
                <div className="ml-auto">
                    <Button onClick={onCreateBooking}>
                        <Plus />
                        Ny bokning
                    </Button>
                </div>
            )}
        </div>
    );
}
