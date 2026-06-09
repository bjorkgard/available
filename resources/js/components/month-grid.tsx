import type { DateInfo, GridDate } from '@/lib/calendar-utils';
import { getWeekdayNames } from '@/lib/calendar-utils';
import { cn } from '@/lib/utils';

interface MonthGridProps {
    grid: GridDate[];
    today: DateInfo | null;
    onFillerDateClick: (year: number, month: number) => void;
}

export function MonthGrid({ grid, today, onFillerDateClick }: MonthGridProps) {
    const weekdays = getWeekdayNames();

    return (
        <div className="grid h-full grid-cols-7 grid-rows-[auto_repeat(6,1fr)]">
            {weekdays.map((name) => (
                <div
                    key={name}
                    className="flex items-center justify-center border-b py-2 text-sm font-medium text-muted-foreground"
                >
                    {name}
                </div>
            ))}

            {grid.map((date, index) => {
                const isToday =
                    date.isToday &&
                    date.isCurrentMonth &&
                    today !== null &&
                    date.day === today.day &&
                    date.month === today.month &&
                    date.year === today.year;

                if (!date.isCurrentMonth) {
                    return (
                        <button
                            key={index}
                            type="button"
                            onClick={() =>
                                onFillerDateClick(date.year, date.month)
                            }
                            className="flex flex-col items-start border p-2 text-muted-foreground hover:bg-accent"
                        >
                            <span className="text-sm leading-none">
                                {date.day}
                            </span>
                        </button>
                    );
                }

                return (
                    <div
                        key={index}
                        className={cn(
                            'flex flex-col items-start overflow-hidden border p-2',
                            isToday &&
                                'rounded-md border-2 border-blue-500 font-semibold',
                        )}
                    >
                        <span className="text-sm leading-none">
                            {date.day}
                        </span>
                        <div className="mt-1 flex w-full flex-1 flex-col gap-0.5 overflow-y-auto">
                            {/* Bookings will render here */}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
