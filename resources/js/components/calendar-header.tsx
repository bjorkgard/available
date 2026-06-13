import { CalendarDays, ChevronLeft, ChevronRight, Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
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
import { getAppLocale } from '@/lib/locale';

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
    const formatter = new Intl.DateTimeFormat(getAppLocale(), {
        month: 'long',
    });

    return Array.from({ length: 12 }, (_, i) =>
        formatter.format(new Date(2025, i, 1)),
    );
}

function getYearRange(): number[] {
    const currentYear = new Date().getFullYear();

    return Array.from({ length: 11 }, (_, i) => currentYear - 5 + i);
}

function formatDayContext(year: number, month: number, day: number): string {
    const formatter = new Intl.DateTimeFormat(getAppLocale(), {
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

    const formatter = new Intl.DateTimeFormat(getAppLocale(), {
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
    const { t } = useTranslation();
    const monthNames = getMonthNames();
    const years = getYearRange();

    const navLabels = (() => {
        switch (viewMode) {
            case 'month':
                return {
                    previous: t('Föregående månad'),
                    next: t('Nästa månad'),
                };
            case 'week':
                return {
                    previous: t('Föregående vecka'),
                    next: t('Nästa vecka'),
                };
            case 'day':
                return { previous: t('Föregående dag'), next: t('Nästa dag') };
        }
    })();

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
                            ←
                        </kbd>
                    </p>
                </TooltipContent>
            </Tooltip>

            <Select
                value={String(displayedMonth)}
                onValueChange={(value) => onSelectMonth(Number(value))}
            >
                <SelectTrigger aria-label={t('Välj månad')}>
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
                <SelectTrigger aria-label={t('Välj år')}>
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
                            →
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
                        {t('Idag')}
                    </Button>
                </TooltipTrigger>
                <TooltipContent>
                    <p>{t('Gå till idag')}</p>
                </TooltipContent>
            </Tooltip>

            <Tooltip>
                <DropdownMenu>
                    <TooltipTrigger asChild>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="outline"
                                size="icon"
                                aria-label={t('Byt vy')}
                            >
                                <CalendarDays />
                            </Button>
                        </DropdownMenuTrigger>
                    </TooltipTrigger>
                    <DropdownMenuContent align="start">
                        <DropdownMenuLabel>{t('Vy')}</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuRadioGroup
                            value={viewMode}
                            onValueChange={(value) =>
                                onViewModeChange(value as ViewMode)
                            }
                        >
                            <DropdownMenuRadioItem value="month">
                                {t('Månad')}
                                <DropdownMenuShortcut>⌘0</DropdownMenuShortcut>
                            </DropdownMenuRadioItem>
                            <DropdownMenuRadioItem value="week">
                                {t('Vecka')}
                                <DropdownMenuShortcut>⌘1</DropdownMenuShortcut>
                            </DropdownMenuRadioItem>
                            <DropdownMenuRadioItem value="day">
                                {t('Dag')}
                                <DropdownMenuShortcut>⌘2</DropdownMenuShortcut>
                            </DropdownMenuRadioItem>
                        </DropdownMenuRadioGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
                <TooltipContent>
                    <p>{t('Byt vy')}</p>
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
                        {t('Ny bokning')}
                    </Button>
                </div>
            )}
        </div>
    );
}
