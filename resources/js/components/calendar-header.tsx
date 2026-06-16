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
import {
    formatDayContext,
    formatWeekContext,
    getMonthNames,
    getYearRange,
} from '@/lib/calendar-utils';

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
        <div className="flex flex-wrap items-center gap-2">
            <div className="flex items-center">
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            onClick={onPrevious}
                            aria-label={navLabels.previous}
                        >
                            <ChevronLeft className="size-4" />
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

                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            onClick={onNext}
                            aria-label={navLabels.next}
                        >
                            <ChevronRight className="size-4" />
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
            </div>

            <div className="flex items-center gap-1.5">
                <Select
                    value={String(displayedMonth)}
                    onValueChange={(value) => onSelectMonth(Number(value))}
                >
                    <SelectTrigger
                        aria-label={t('Välj månad')}
                        className="h-8 w-auto border-none bg-transparent px-2 text-sm font-medium shadow-none focus:ring-0"
                    >
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
                    <SelectTrigger
                        aria-label={t('Välj år')}
                        className="h-8 w-auto border-none bg-transparent px-2 text-sm font-medium shadow-none focus:ring-0"
                    >
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
            </div>

            <Tooltip>
                <TooltipTrigger asChild>
                    <Button
                        variant="outline"
                        size="sm"
                        className="h-8 px-3 text-xs"
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

            {viewMode === 'week' && (
                <span className="hidden text-sm text-muted-foreground sm:inline">
                    {formatWeekContext(
                        displayedYear,
                        displayedMonth,
                        displayedDay,
                    )}
                </span>
            )}

            {viewMode === 'day' && (
                <span className="hidden text-sm text-muted-foreground sm:inline">
                    {formatDayContext(
                        displayedYear,
                        displayedMonth,
                        displayedDay,
                    )}
                </span>
            )}

            <div className="ml-auto flex items-center gap-1.5">
                <Tooltip>
                    <DropdownMenu>
                        <TooltipTrigger asChild>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="h-8 gap-1.5 px-2.5 text-xs"
                                    aria-label={t('Byt vy')}
                                >
                                    <CalendarDays className="size-3.5" />
                                    <span className="hidden sm:inline">
                                        {viewMode === 'month'
                                            ? t('Månad')
                                            : viewMode === 'week'
                                              ? t('Vecka')
                                              : t('Dag')}
                                    </span>
                                </Button>
                            </DropdownMenuTrigger>
                        </TooltipTrigger>
                        <DropdownMenuContent align="end">
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
                                    <DropdownMenuShortcut>
                                        ⌘0
                                    </DropdownMenuShortcut>
                                </DropdownMenuRadioItem>
                                <DropdownMenuRadioItem value="week">
                                    {t('Vecka')}
                                    <DropdownMenuShortcut>
                                        ⌘1
                                    </DropdownMenuShortcut>
                                </DropdownMenuRadioItem>
                                <DropdownMenuRadioItem value="day">
                                    {t('Dag')}
                                    <DropdownMenuShortcut>
                                        ⌘2
                                    </DropdownMenuShortcut>
                                </DropdownMenuRadioItem>
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <TooltipContent>
                        <p>{t('Byt vy')}</p>
                    </TooltipContent>
                </Tooltip>

                {onCreateBooking && (
                    <Button size="sm" className="h-8" onClick={onCreateBooking}>
                        <Plus className="size-3.5" />
                        <span className="hidden sm:inline">
                            {t('Ny bokning')}
                        </span>
                    </Button>
                )}
            </div>
        </div>
    );
}
